<div>
    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-md-12 col-12 layout-spacing">
            <div class="widget-content-area br-4">
                <div class="widget-header">
                    <div class="row">
                        <div class="col-xl-12 col-md-12 col-sm-12 col-12">
                            <h4>Copias de Seguridad</h4>
                        </div>
                    </div>
                </div>

                <div class="widget-content widget-content-area">
                    <div class="table-responsive">
                        <div class="mb-3">
                            <div class="btn-group">
                                <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" wire:loading.attr="disabled">
                                    <i class="fas fa-plus me-2"></i> Crear Copia de Seguridad
                                </button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="#" wire:click.prevent="create('only-db')">Solo Base de Datos</a>
                                    <a class="dropdown-item" href="#" wire:click.prevent="create('full')">Completa (Archivos + DB)</a>
                                </div>
                            </div>
                            <span wire:loading wire:target="create" class="text-primary ms-2">
                                <i class="fas fa-spinner fa-spin"></i> Creando copia...
                            </span>
                            <span wire:loading wire:target="restore" class="text-warning ms-2">
                                <i class="fas fa-spinner fa-spin"></i> Restaurando BD...
                            </span>
                            
                            <!-- Hidden button for deletion workaround -->
                            <button id="hiddenDeleteBtn" wire:click="deleteBackup" style="display: none;"></button>
                        </div>

                        <table class="table table-bordered table-striped mt-1">
                            <thead class="text-white" style="background: #3b3f5c">
                                <tr>
                                    <th class="table-th text-white">Nombre del Archivo</th>
                                    <th class="table-th text-white text-center">Tamaño</th>
                                    <th class="table-th text-white text-center">Fecha</th>
                                    <th class="table-th text-white text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($backups as $backup)
                                    <tr>
                                        <td>{{ $backup['name'] }}</td>
                                        <td class="text-center">{{ $backup['size'] }}</td>
                                        <td class="text-center">{{ $backup['date'] }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('backups.download', ['fileName' => $backup['name']]) }}" class="btn btn-dark btn-sm" title="Descargar" target="_blank">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <button onclick="ConfirmRestore('{{ $backup['path'] }}')" class="btn btn-warning btn-sm" title="Restaurar BD">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                            <button onclick="ConfirmDelete('{{ $backup['path'] }}')" class="btn btn-danger btn-sm" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">No hay copias de seguridad disponibles</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function ConfirmDelete(path) {
            swal({
                title: 'CONFIRMAR',
                text: '¿CONFIRMAS ELIMINAR LA COPIA DE SEGURIDAD?',
                icon: 'warning',
                showCancelButton: true,
                cancelButtonText: 'Cerrar',
                confirmButtonColor: '#3b3f5c',
                confirmButtonText: 'Aceptar',
                buttons: true,
                dangerMode: false
            }).then(function(result) {
                if (result) {
                    try {
                        // Set the path property
                        @this.set('selectedPath', path);
                        // Click the hidden button to trigger the action
                        setTimeout(() => {
                            document.getElementById('hiddenDeleteBtn').click();
                        }, 100);
                    } catch (e) {
                        console.error(e);
                        swal({
                            title: 'Error',
                            text: 'Ocurrió un error inesperado: ' + e.message,
                            icon: 'error'
                        });
                    }
                }
            })
        }

        function ConfirmRestore(path) {
            swal({
                title: 'ADVERTENCIA CRÍTICA',
                text: '¿Estás seguro de RESTAURAR la base de datos? Esto SOBREESCRIBIRÁ todos los datos actuales con los de la copia. ¡Esta acción no se puede deshacer!',
                icon: 'error',
                showCancelButton: true,
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#e7515a',
                confirmButtonText: 'SÍ, RESTAURAR',
                buttons: true,
                dangerMode: true
            }).then(function(result) {
                if (result) {
                    Livewire.dispatch('restore-backup', { path: path });
                }
            })
        }
    </script>
</div>
