<?php

namespace App\Livewire\WhatsApp;

use App\Services\WhatsAppService;
use Livewire\Component;
use Mary\Traits\Toast;

class SessionManager extends Component
{
    use Toast;

    public string $status = 'UNKNOWN';

    public ?string $previousStatus = null;

    public ?string $qrCode = null;

    public ?string $phoneName = null;

    public ?string $engineInfo = null;

    public bool $showChangeNumberModal = false;

    public bool $isWaitingForScan = false;

    public string $testPhone = '';

    public string $testMessage = 'Ini adalah uji coba notifikasi WhatsApp dari Scholaproc.';

    public bool $testSending = false;

    public function mount(): void
    {
        $this->authorizeSuperAdmin();
        $this->refreshStatus();
    }

    private function authorizeSuperAdmin(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }

    public function pollStatus(): void
    {
        $this->authorizeSuperAdmin();

        // Hanya polling aktif saat memindai QR atau memulai session agar QR otomatis
        // diperbarui dan status berubah ke terhubung setelah di-scan.
        if (! in_array($this->status, ['SCAN_QR_CODE', 'STARTING'], true)) {
            return;
        }

        /** @var WhatsAppService $wa */
        $wa = app(WhatsAppService::class);

        $info = $wa->getSessionInfo();

        $newStatus = $info ? ($info['status'] ?? 'UNKNOWN') : 'UNAVAILABLE';

        if ($newStatus === $this->status) {
            return;
        }

        $this->previousStatus = $this->status;
        $this->status = $newStatus;
        $this->phoneName = $info['me']['pushName'] ?? null;
        $this->engineInfo = $info['engine']['engine'] ?? null;

        if ($this->status === 'SCAN_QR_CODE') {
            $this->isWaitingForScan = true;
            $this->loadQR();
        } else {
            $this->qrCode = null;

            if ($this->isWaitingForScan && $this->status === 'WORKING') {
                $this->isWaitingForScan = false;
                $this->success('Scan berhasil! WhatsApp terhubung.', position: 'toast-top toast-end');
            } else {
                $this->isWaitingForScan = false;
            }
        }

        if ($this->previousStatus === 'WORKING' && in_array($this->status, ['STOPPED', 'TIMEOUT', 'UNKNOWN'])) {
            $this->warning('WhatsApp terputus. Mencoba mengulang koneksi otomatis...', position: 'toast-top toast-end');
            $this->autoRestart();
        }
    }

    protected function autoRestart(): void
    {
        /** @var WhatsAppService $wa */
        $wa = app(WhatsAppService::class);

        $result = $wa->startSession();

        if ($result['success']) {
            $this->info('Session WhatsApp dimulai ulang secara otomatis.', position: 'toast-top toast-end');
            $this->refreshStatus();
        } else {
            $this->error('Gagal mengulang session secara otomatis: '.($result['error'] ?? 'Unknown error'), position: 'toast-top toast-end');
        }
    }

    public function refreshStatus(): void
    {
        $this->authorizeSuperAdmin();

        /** @var WhatsAppService $wa */
        $wa = app(WhatsAppService::class);

        $info = $wa->getSessionInfo();

        if ($info) {
            $this->status = $info['status'] ?? 'UNKNOWN';
            $this->phoneName = $info['me']['pushName'] ?? null;
            $this->engineInfo = $info['engine']['engine'] ?? null;
        } else {
            $this->status = 'UNAVAILABLE';
        }

        if ($this->status === 'SCAN_QR_CODE') {
            $this->loadQR();
        } else {
            $this->qrCode = null;
        }
    }

    public function loadQR(): void
    {
        $this->authorizeSuperAdmin();

        /** @var WhatsAppService $wa */
        $wa = app(WhatsAppService::class);
        $this->qrCode = $wa->getQRCode();
    }

    public function startSession(): void
    {
        $this->authorizeSuperAdmin();

        /** @var WhatsAppService $wa */
        $wa = app(WhatsAppService::class);

        $result = $wa->startSession();

        if ($result['success']) {
            $this->success('Session WhatsApp dimulai. Menunggu QR code...', position: 'toast-top toast-end');
            $this->refreshStatus();
        } else {
            $this->error('Gagal memulai session: '.($result['error'] ?? 'Unknown error'));
        }
    }

    public function stopSession(): void
    {
        $this->authorizeSuperAdmin();

        /** @var WhatsAppService $wa */
        $wa = app(WhatsAppService::class);

        $result = $wa->stopSession();

        if ($result['success']) {
            $this->success('Session WhatsApp dihentikan.');
            $this->refreshStatus();
        } else {
            $this->error('Gagal menghentikan session: '.($result['error'] ?? 'Unknown error'));
        }
    }

    public function restartSession(): void
    {
        $this->authorizeSuperAdmin();

        /** @var WhatsAppService $wa */
        $wa = app(WhatsAppService::class);

        $result = $wa->startSession();

        if ($result['success']) {
            $this->success('Session WhatsApp dimulai ulang.');
            $this->refreshStatus();
        } else {
            $this->error('Gagal memulai ulang session: '.($result['error'] ?? 'Unknown error'));
        }
    }

    public function openChangeNumberModal(): void
    {
        $this->showChangeNumberModal = true;
    }

    public function closeChangeNumberModal(): void
    {
        $this->showChangeNumberModal = false;
    }

    public function logoutSession(): void
    {
        $this->authorizeSuperAdmin();

        /** @var WhatsAppService $wa */
        $wa = app(WhatsAppService::class);

        $result = $wa->logout();

        if ($result['success']) {
            $this->success('Berhasil logout dari WhatsApp. Memulai session baru...', position: 'toast-top toast-end');
            $this->showChangeNumberModal = false;
            $this->startSession();
        } else {
            $this->error('Gagal logout: '.($result['error'] ?? 'Unknown error'));
        }
    }

    public function testSend(): void
    {
        $this->authorizeSuperAdmin();

        $this->validate([
            'testPhone' => 'required|string',
            'testMessage' => 'required|string',
        ]);

        $this->testSending = true;

        /** @var WhatsAppService $wa */
        $wa = app(WhatsAppService::class);
        $result = $wa->send($this->testPhone, $this->testMessage);

        $this->testSending = false;

        if ($result['success']) {
            $this->success('Pesan uji coba WhatsApp berhasil dikirim.');
        } else {
            $this->error('Gagal mengirim pesan uji coba: '.($result['error'] ?? 'Unknown error'));
        }
    }

    public function getStatusBadge(): string
    {
        return match ($this->status) {
            'WORKING' => 'badge-success',
            'SCAN_QR_CODE' => 'badge-warning',
            'STARTING' => 'badge-info',
            'STOPPED' => 'badge-error',
            'TIMEOUT' => 'badge-error',
            'DISCONNECTED' => 'badge-error',
            default => 'badge-ghost',
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'WORKING' => 'Terhubung',
            'SCAN_QR_CODE' => 'Menunggu Scan QR',
            'STARTING' => 'Memulai...',
            'STOPPED' => 'Terhenti',
            'TIMEOUT' => 'Timeout',
            'DISCONNECTED' => 'Terputus',
            'UNAVAILABLE' => 'Tidak Terjangkau',
            default => $this->status,
        };
    }

    public function render()
    {
        return view('livewire.whatsapp.session-manager')->layout('layouts.app');
    }
}
