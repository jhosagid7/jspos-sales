@extends('install.layout')

@section('content')
    <h4 class="mb-3">Paso 4: Activación de Licencia</h4>

    <ul class="nav nav-pills nav-fill mb-4" id="licenseTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active font-weight-bold" id="auto-tab" data-bs-toggle="tab" data-bs-target="#auto-pane" type="button" role="tab">
                <i class="fas fa-network-wired me-1"></i> Opción A: Conexión Automática (VPN)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link font-weight-bold" id="manual-tab" data-bs-toggle="tab" data-bs-target="#manual-pane" type="button" role="tab">
                <i class="fas fa-key me-1"></i> Opción B: Código Manual
            </button>
        </li>
    </ul>

    <div class="tab-content" id="licenseTabsContent">
        <!-- TAB 1: AUTOMATIC VPN -->
        <div class="tab-pane fade show active" id="auto-pane" role="tabpanel">
            <div class="card border-primary mb-3">
                <div class="card-body">
                    <h5 class="card-title text-primary"><i class="fas fa-magic me-1"></i> Registro y Activación por Red VPN</h5>
                    <p class="text-muted small">Al ingresar la IP del servidor de licencias, este equipo se registrará automáticamente en el panel del administrador como <strong>🟢 En Línea</strong>.</p>
                    
                    <form id="vpnConnectForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-bold">IP del Servidor de Licencias (ZeroTier / Tailscale):</label>
                            <input type="text" id="server_ip" class="form-control" value="{{ $defaultServerIp }}" placeholder="Ej. 100.x.x.x:8080 o mi-servidor.com" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Nombre del Negocio / Tienda:</label>
                            <input type="text" id="client_name" class="form-control" placeholder="Ej. Comercial Los Llanos" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted small font-weight-bold">IP VPN de este Equipo (Opcional):</label>
                            <div class="input-group input-group-sm mb-1">
                                <input type="text" id="client_vpn_ip" class="form-control form-control-sm" value="{{ $defaultVpnIp ?? '' }}" placeholder="Ej. 100.115.149.91 o 100.115.149.91:8081">
                                @if(!empty($vpnIps) && count($vpnIps) > 1)
                                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-list me-1"></i> Seleccionar VPN
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow">
                                        @foreach($vpnIps as $label => $ip)
                                            <li>
                                                <a class="dropdown-item small py-2" href="#" onclick="document.getElementById('client_vpn_ip').value = '{{ $ip }}'; return false;">
                                                    <i class="fas fa-network-wired text-primary me-2"></i><strong>{{ $label }}:</strong> {{ $ip }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>

                            @if(!empty($vpnIps))
                                <div class="mt-2 bg-light p-2 rounded border">
                                    <span class="text-muted small d-block mb-1 font-weight-bold"><i class="fas fa-network-wired text-primary me-1"></i> IPs VPN / Redes detectadas en este equipo (Haz clic para elegir):</span>
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($vpnIps as $label => $ip)
                                            <button type="button" class="btn btn-sm {{ str_contains(strtolower($label), 'zerotier') ? 'btn-outline-warning text-dark' : (str_contains(strtolower($label), 'tailscale') ? 'btn-outline-info text-dark' : 'btn-outline-secondary') }} py-1 px-2 me-1 text-nowrap mb-1" onclick="document.getElementById('client_vpn_ip').value = '{{ $ip }}'">
                                                <i class="fas fa-plug me-1"></i><strong>{{ $label }}:</strong> {{ $ip }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="d-flex justify-content-between items-center mt-3">
                            <div class="text-muted small font-monospace">
                                ID Sistema: <strong>{{ substr($clientId, 0, 13) }}...</strong>
                            </div>
                            <button type="submit" id="btnConnect" class="btn btn-primary">
                                <i class="fas fa-wifi me-1"></i> Registrar y Conectar
                            </button>
                        </div>
                    </form>

                    <!-- Status Response Box -->
                    <div id="statusAlert" class="alert mt-3 d-none" role="alert">
                        <div id="statusMessage"></div>
                        <div class="mt-2 text-end d-none" id="checkStatusContainer">
                            <button type="button" id="btnCheckStatus" class="btn btn-sm btn-outline-dark">
                                <i class="fas fa-sync-alt me-1"></i> Consultar si mi Licencia ya fue aprobada
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: MANUAL KEY -->
        <div class="tab-pane fade" id="manual-pane" role="tabpanel">
            <div class="alert alert-info">
                <p class="mb-1">Envíe el siguiente ID a su proveedor para obtener su licencia firmada:</p>
                <h3 class="text-center my-2 font-monospace user-select-all bg-white p-2 rounded border">{{ $clientId }}</h3>
            </div>

            <form action="{{ route('install.activateLicense') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-bold">Pegue su Código de Licencia aquí:</label>
                    <textarea name="license_key" class="form-control" rows="5" required placeholder="Pegue la clave larga base64 aquí..."></textarea>
                </div>

                <div class="text-end mt-3">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check-circle me-1"></i> Activar y Continuar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const vpnForm = document.getElementById('vpnConnectForm');
        const btnConnect = document.getElementById('btnConnect');
        const statusAlert = document.getElementById('statusAlert');
        const statusMessage = document.getElementById('statusMessage');
        const checkStatusContainer = document.getElementById('checkStatusContainer');
        const btnCheckStatus = document.getElementById('btnCheckStatus');

        let lastServerIp = '';

        vpnForm.addEventListener('submit', function (e) {
            e.preventDefault();
            btnConnect.disabled = true;
            btnConnect.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Conectando...';

            statusAlert.className = 'alert alert-info mt-3';
            statusAlert.classList.remove('d-none');
            statusMessage.innerHTML = 'Enviando registro al servidor de licencias...';

            lastServerIp = document.getElementById('server_ip').value.trim();

            fetch('{{ route("install.connectLicenseServer") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    server_ip: lastServerIp,
                    client_name: document.getElementById('client_name').value.trim(),
                    client_vpn_ip: document.getElementById('client_vpn_ip').value.trim()
                })
            })
            .then(res => res.json())
            .then(data => {
                btnConnect.disabled = false;
                btnConnect.innerHTML = '<i class="fas fa-wifi me-1"></i> Registrar y Conectar';

                if (data.status === 'activated') {
                    statusAlert.className = 'alert alert-success mt-3';
                    statusMessage.innerHTML = '<strong>¡Éxito!</strong> ' + data.message;
                    setTimeout(() => window.location.href = data.redirect, 1500);
                } else if (data.status === 'registered') {
                    statusAlert.className = 'alert alert-warning mt-3';
                    statusMessage.innerHTML = '<strong>' + data.message + '</strong>';
                    checkStatusContainer.classList.remove('d-none');
                } else {
                    statusAlert.className = 'alert alert-danger mt-3';
                    statusMessage.innerHTML = '<strong>Error:</strong> ' + (data.message || 'No se pudo conectar.');
                }
            })
            .catch(err => {
                btnConnect.disabled = false;
                btnConnect.innerHTML = '<i class="fas fa-wifi me-1"></i> Registrar y Conectar';
                statusAlert.className = 'alert alert-danger mt-3';
                statusMessage.innerHTML = '<strong>Error de Conexión:</strong> Compruebe la IP VPN ingresada.';
            });
        });

        btnCheckStatus.addEventListener('click', function () {
            btnCheckStatus.disabled = true;
            btnCheckStatus.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Consultando...';

            fetch('{{ route("install.checkLicenseStatus") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ server_ip: lastServerIp })
            })
            .then(res => res.json())
            .then(data => {
                btnCheckStatus.disabled = false;
                btnCheckStatus.innerHTML = '<i class="fas fa-sync-alt me-1"></i> Consultar si mi Licencia ya fue aprobada';

                if (data.status === 'activated') {
                    statusAlert.className = 'alert alert-success mt-3';
                    statusMessage.innerHTML = '<strong>¡Licencia aprobada y activada!</strong> Redirigiendo...';
                    setTimeout(() => window.location.href = data.redirect, 1500);
                } else {
                    alert(data.message || 'Aún en espera de aprobación.');
                }
            });
        });
    });
    </script>
@endsection
