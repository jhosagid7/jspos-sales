<div>
    <!-- Modals -->
    <!-- Modal: Registrar Gasto -->
    <div class="modal fade @if($showExpenseModal) show d-block @endif" tabindex="-1" role="dialog" style="background: rgba(0,0,0,0.5); overflow-y: auto;">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-danger text-white rounded-top">
                    <h5 class="modal-title text-white"><i class="fas fa-arrow-down"></i> Registrar Gasto Bancario</h5>
                    <button type="button" class="close text-white" wire:click="$set('showExpenseModal', false)">&times;</button>
                </div>
                <form wire:submit.prevent="saveExpense">
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="font-weight-bold">Banco Emisor</label>
                            <select wire:model="expense_bank_id" class="form-control">
                                @foreach($allBanks as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->name }} ({{ $bank->currency_code }})</option>
                                @endforeach
                            </select>
                            @error('expense_bank_id') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Categoría de Gasto</label>
                            <select wire:model.live="expense_category_id" class="form-control">
                                <option value="">-- SELECCIONE CATEGORÍA DE GASTO --</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ mb_strtoupper($cat->name) }}</option>
                                @endforeach
                                <option value="NEW" class="font-weight-bold text-primary">➕ OTRO (CREAR NUEVA CATEGORÍA)</option>
                            </select>
                            @error('expense_category_id') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        @if($expense_category_id === 'NEW')
                            <div class="form-group border p-2 rounded bg-light">
                                <label class="font-weight-bold text-primary"><i class="fas fa-folder-plus"></i> Nombre de la Nueva Categoría de Gasto</label>
                                <input type="text" wire:model="new_expense_category_name" class="form-control text-uppercase" placeholder="EJ: FLETES ESPECIALES, DONACIONES..." style="text-transform: uppercase;">
                                <small class="form-text text-muted">Se convertirá automáticamente a MAYÚSCULAS para mantener el formato único.</small>
                                @error('new_expense_category_name') <span class="text-danger small d-block">{{ $message }}</span> @enderror
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Monto</label>
                                    <input type="number" step="0.01" wire:model="expense_amount" class="form-control" placeholder="0.00">
                                    @error('expense_amount') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Fecha de Gasto</label>
                                    <input type="date" wire:model="expense_date" class="form-control">
                                    @error('expense_date') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Beneficiario</label>
                                    <input type="text" wire:model="expense_beneficiary" class="form-control" placeholder="Ej: CANTV, Alquiler Corp...">
                                    @error('expense_beneficiary') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Referencia / Nro Transf.</label>
                                    <input type="text" wire:model="expense_reference" class="form-control" placeholder="Nro Transferencia...">
                                    @error('expense_reference') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Descripción / Nota</label>
                            <textarea wire:model="expense_description" class="form-control" rows="2" placeholder="Nota descriptiva del gasto..."></textarea>
                            @error('expense_description') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Comprobante de Pago</label>
                            <input type="file" wire:model="expense_receipt" class="form-control-file">
                            <div wire:loading wire:target="expense_receipt" class="text-primary small mt-1">Cargando archivo...</div>
                            @error('expense_receipt') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="custom-control custom-checkbox mt-2">
                            <input type="checkbox" wire:model="expense_is_recurring" class="custom-control-input" id="expense_is_recurring">
                            <label class="custom-control-label font-weight-bold" for="expense_is_recurring">¿Es un gasto fijo/recurrente mensual?</label>
                        </div>
                    </div>
                    <div class="modal-footer bg-light rounded-bottom">
                        <button type="button" class="btn btn-secondary btn-sm" wire:click="$set('showExpenseModal', false)">Cancelar</button>
                        <button type="submit" class="btn btn-danger btn-sm" wire:loading.attr="disabled"><i class="fas fa-save"></i> Registrar Gasto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Registrar Transferencia -->
    <div class="modal fade @if($showTransferModal) show d-block @endif" tabindex="-1" role="dialog" style="background: rgba(0,0,0,0.5); overflow-y: auto;">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white rounded-top">
                    <h5 class="modal-title text-white"><i class="fas fa-exchange-alt"></i> Nueva Transferencia entre Bancos</h5>
                    <button type="button" class="close text-white" wire:click="$set('showTransferModal', false)">&times;</button>
                </div>
                <form wire:submit.prevent="saveTransfer">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Origen (Debitase)</label>
                                    <select wire:model.live="transfer_from_bank_id" class="form-control">
                                        @foreach($allBanks as $bank)
                                            <option value="{{ $bank->id }}">{{ $bank->name }} ({{ $bank->currency_code }})</option>
                                        @endforeach
                                    </select>
                                    @error('transfer_from_bank_id') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Destino (Acreditase)</label>
                                    <select wire:model.live="transfer_to_bank_id" class="form-control">
                                        @foreach($allBanks as $bank)
                                            <option value="{{ $bank->id }}">{{ $bank->name }} ({{ $bank->currency_code }})</option>
                                        @endforeach
                                    </select>
                                    @error('transfer_to_bank_id') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Monto Origen</label>
                                    <input type="number" step="0.01" wire:model.live="transfer_amount_from" class="form-control" placeholder="0.00">
                                    @error('transfer_amount_from') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Monto Destino</label>
                                    <input type="number" step="0.01" wire:model="transfer_amount_to" class="form-control" placeholder="0.00">
                                    @error('transfer_amount_to') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Tasa de Cambio</label>
                                    <input type="number" step="0.000001" wire:model.live="transfer_exchange_rate" class="form-control">
                                    @error('transfer_exchange_rate') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Fecha</label>
                                    <input type="date" wire:model="transfer_date" class="form-control">
                                    @error('transfer_date') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Referencia / Nro Transf.</label>
                            <input type="text" wire:model="transfer_reference" class="form-control" placeholder="Nro Transferencia...">
                            @error('transfer_reference') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Notas / Justificación</label>
                            <textarea wire:model="transfer_notes" class="form-control" rows="2" placeholder="Motivo de la transferencia..."></textarea>
                            @error('transfer_notes') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="modal-footer bg-light rounded-bottom">
                        <button type="button" class="btn btn-secondary btn-sm" wire:click="$set('showTransferModal', false)">Cancelar</button>
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-check"></i> Transferir</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Registrar Otro Ingreso Bancario -->
    <div class="modal fade @if($showOtherIncomeModal) show d-block @endif" tabindex="-1" role="dialog" style="background: rgba(0,0,0,0.5); overflow-y: auto;">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-success text-white rounded-top">
                    <h5 class="modal-title text-white"><i class="fas fa-plus-circle"></i> Registrar Otro Ingreso Bancario</h5>
                    <button type="button" class="close text-white" wire:click="$set('showOtherIncomeModal', false)">&times;</button>
                </div>
                <form wire:submit.prevent="saveOtherIncome">
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="font-weight-bold">Banco Receptor</label>
                            <select wire:model="other_income_bank_id" class="form-control">
                                @foreach($allBanks as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->name }} ({{ $bank->currency_code }})</option>
                                @endforeach
                            </select>
                            @error('other_income_bank_id') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Concepto / Categoría de Ingreso</label>
                            <select wire:model.live="other_income_category" class="form-control">
                                <option value="">-- SELECCIONE TIPO DE INGRESO --</option>
                                @foreach($incomeCategories as $incCat)
                                    <option value="{{ $incCat }}">{{ mb_strtoupper($incCat) }}</option>
                                @endforeach
                                <option value="NEW" class="font-weight-bold text-success">➕ OTRO (CREAR NUEVO TIPO DE INGRESO)</option>
                            </select>
                            @error('other_income_category') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        @if($other_income_category === 'NEW')
                            <div class="form-group border p-2 rounded bg-light">
                                <label class="font-weight-bold text-success"><i class="fas fa-plus-circle"></i> Nombre del Nuevo Tipo de Ingreso</label>
                                <input type="text" wire:model="new_income_category_name" class="form-control text-uppercase" placeholder="EJ: SUBSIDIO, INYECCIÓN DE SOCIOS..." style="text-transform: uppercase;">
                                <small class="form-text text-muted">Se convertirá automáticamente a MAYÚSCULAS para mantener el formato único.</small>
                                @error('new_income_category_name') <span class="text-danger small d-block">{{ $message }}</span> @enderror
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Monto a Ingresar</label>
                                    <input type="number" step="0.01" wire:model="other_income_amount" class="form-control" placeholder="0.00">
                                    @error('other_income_amount') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Fecha del Ingreso</label>
                                    <input type="date" wire:model="other_income_date" class="form-control">
                                    @error('other_income_date') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Referencia / Nro Transf.</label>
                            <input type="text" wire:model="other_income_reference" class="form-control" placeholder="Nro de depósito o referencia...">
                            @error('other_income_reference') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Descripción / Actividad a la que Pertenece</label>
                            <textarea wire:model="other_income_description" class="form-control" rows="2" placeholder="Detalle a qué pertenece o corresponde este ingreso..."></textarea>
                            @error('other_income_description') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Comprobante / Captura (Opcional)</label>
                            <input type="file" wire:model="other_income_receipt" class="form-control-file">
                            @error('other_income_receipt') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="modal-footer bg-light rounded-bottom">
                        <button type="button" class="btn btn-secondary btn-sm" wire:click="$set('showOtherIncomeModal', false)">Cancelar</button>
                        <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-check-circle"></i> Guardar Ingreso</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Apertura de Jornada Bancaria -->
    <div class="modal fade @if($showOpeningModal) show d-block @endif" tabindex="-1" role="dialog" style="background: rgba(0,0,0,0.5); overflow-y: auto;">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-warning text-dark rounded-top">
                    <h5 class="modal-title font-weight-bold text-dark"><i class="fas fa-sun"></i> Apertura de Jornada Bancaria (Mañana)</h5>
                    <button type="button" class="close text-dark" wire:click="$set('showOpeningModal', false)">&times;</button>
                </div>
                <form wire:submit.prevent="saveOpening">
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="font-weight-bold">Cuenta Bancaria</label>
                            <select wire:model="opening_bank_id" class="form-control">
                                @foreach($allBanks as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->name }} ({{ $bank->currency_code }})</option>
                                @endforeach
                            </select>
                            @error('opening_bank_id') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Fecha de Apertura</label>
                            <input type="date" wire:model="opening_date" class="form-control">
                            @error('opening_date') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Monto Inicial en Banco Real (Ingresado por Operador)</label>
                            <input type="number" step="0.01" wire:model="opening_manual_balance" class="form-control" placeholder="0.00">
                            <small class="form-text text-muted">Ingresa el saldo exacto que muestra el portal del banco al iniciar la jornada en la mañana.</small>
                            @error('opening_manual_balance') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Captura de Pantalla del Banco (Opcional)</label>
                            <input type="file" wire:model="opening_proof_image" class="form-control-file">
                            @error('opening_proof_image') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="modal-footer bg-light rounded-bottom">
                        <button type="button" class="btn btn-secondary btn-sm" wire:click="$set('showOpeningModal', false)">Cancelar</button>
                        <button type="submit" class="btn btn-warning btn-sm font-weight-bold text-dark"><i class="fas fa-sun"></i> Registrar Apertura</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Realizar Corte Diario Auditado -->
    <div class="modal fade @if($showClosureModal) show d-block @endif" tabindex="-1" role="dialog" style="background: rgba(0,0,0,0.5); overflow-y: auto;">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white rounded-top">
                    <h5 class="modal-title text-white"><i class="fas fa-moon"></i> Realizar Corte Diario Bancario (Tarde/Noche)</h5>
                    <button type="button" class="close text-white" wire:click="$set('showClosureModal', false)">&times;</button>
                </div>
                <form wire:submit.prevent="saveClosure">
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="font-weight-bold">Banco a Cerrar</label>
                            <select wire:model="closure_bank_id" class="form-control">
                                @foreach($allBanks as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->name }} ({{ $bank->currency_code }})</option>
                                @endforeach
                            </select>
                            @error('closure_bank_id') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Fecha del Cierre</label>
                            <input type="date" wire:model="closure_date" class="form-control">
                            @error('closure_date') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Monto de Cierre Real en Banco (Operador) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" wire:model="closure_manual_balance" class="form-control" placeholder="0.00">
                            <small class="form-text text-muted">Ingresa el saldo final que muestra el portal del banco al terminar el día.</small>
                            @error('closure_manual_balance') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold text-danger">Captura / Comprobante del Banco (Obligatorio) <span class="text-danger">*</span></label>
                            <input type="file" wire:model="closure_proof_image" class="form-control-file">
                            <small class="form-text text-muted">Sube una foto o captura de pantalla del estado de cuenta del banco al momento del cierre.</small>
                            @error('closure_proof_image') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Observaciones / Notas del Cierre</label>
                            <textarea wire:model="closure_notes" class="form-control" rows="2" placeholder="Ingresa detalles como descuadres encontrados, comentarios del operador, etc."></textarea>
                            @error('closure_notes') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="modal-footer bg-light rounded-bottom">
                        <button type="button" class="btn btn-secondary btn-sm" wire:click="$set('showClosureModal', false)">Cancelar</button>
                        <button type="submit" class="btn btn-dark btn-sm"><i class="fas fa-lock"></i> Ejecutar Corte Auditado</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Main Container -->
    <div class="row layout-top-spacing">
        <!-- Summary Cards / Key Indicators -->
        <div class="col-xl-12 col-lg-12 col-md-12 col-12 mb-3">
            <div class="card border-0 shadow-sm rounded">
                <div class="card-body bg-light rounded p-3 d-flex justify-content-between align-items-center flex-wrap">
                    <div class="mb-2">
                        <h4 class="mb-0 text-dark font-weight-bold"><i class="fas fa-university text-primary"></i> Caja y Bancos (Tesorería)</h4>
                        <span class="text-muted small">Control de ingresos, gastos, cortes y balances en tiempo real</span>
                    </div>
                    <div class="d-flex flex-wrap" style="gap: 8px;">
                        @can('treasury.expenses')
                            <button wire:click="openOtherIncomeModal" class="btn btn-success btn-sm rounded shadow-sm">
                                <i class="fas fa-plus-circle"></i> Otro Ingreso
                            </button>
                            <button wire:click="openExpenseModal" class="btn btn-danger btn-sm rounded shadow-sm">
                                <i class="fas fa-minus-circle"></i> Registrar Gasto
                            </button>
                            <button wire:click="openTransferModal" class="btn btn-primary btn-sm rounded shadow-sm">
                                <i class="fas fa-exchange-alt"></i> Transferencia
                            </button>
                        @endcan
                        @can('treasury.closure')
                            <button wire:click="openOpeningModal" class="btn btn-warning btn-sm text-dark font-weight-bold rounded shadow-sm">
                                <i class="fas fa-sun"></i> Apertura de Banco
                            </button>
                            <button wire:click="openClosureModal" class="btn btn-dark btn-sm rounded shadow-sm">
                                <i class="fas fa-moon"></i> Corte Diario
                            </button>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        <!-- Tracked Banks Summaries -->
        <div class="col-xl-12 col-lg-12 col-md-12 col-12 mb-4">
            <div class="row">
                <!-- Total Balance in USD -->
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3">
                    <div class="card border-0 shadow-sm bg-gradient-success text-white h-100 rounded">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-white-50 f-12 font-weight-bold text-uppercase">PATRIMONIO BANCARIO (TOTAL)</span>
                                <i class="fas fa-wallet fa-2x text-white-50" title="Suma consolidada de todas las cuentas bancarias auditadas convertidas a la moneda principal del sistema."></i>
                            </div>
                            <h2 class="text-white font-weight-bold mt-2">${{ number_format($totalInPrimary, 2) }} <span class="f-14">{{ $primaryCode }}</span></h2>
                            <span class="text-white-50 small font-weight-bold"><i class="fas fa-info-circle mr-1"></i>Suma consolidada en la moneda principal</span>
                        </div>
                    </div>
                </div>

                @foreach($balances as $bankId => $b)
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3">
                        <div class="card border-0 shadow-sm h-100 rounded border-left border-info">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted f-11 font-weight-bold text-uppercase" title="Saldo conciliado en tiempo real con transacciones y egresos registrados.">{{ $b['name'] }}</span>
                                    <span class="badge badge-info text-white font-weight-bold">{{ $b['currency'] }}</span>
                                </div>
                                <h3 class="font-weight-bold text-dark mt-2">
                                    @if($b['currency'] === 'VED')
                                        Bs. {{ number_format($b['balance'], 2) }}
                                    @elseif($b['currency'] === 'COP')
                                        COP {{ number_format($b['balance'], 0, ',', '.') }}
                                    @elseif($b['currency'] === 'USD')
                                        ${{ number_format($b['balance'], 2) }}
                                    @else
                                        {{ $b['currency'] }} {{ number_format($b['balance'], 2) }}
                                    @endif
                                </h3>
                                @if($b['currency'] !== 'USD')
                                    <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                        <i class="fas fa-exchange-alt mr-1"></i> Equiv: <strong class="text-secondary">${{ number_format($b['balance_primary'], 2) }} USD</strong>
                                        <span class="text-muted ml-1" style="font-size: 9px;">(Tasa: {{ number_format($b['rate'], 2) }} {{ $b['currency'] }})</span>
                                    </small>
                                @endif
                                <div class="d-flex justify-content-between mt-2 pt-2 border-top text-muted small">
                                    <span>Ingresos hoy: <strong class="text-success">+{{ number_format($b['income_today'], 2) }}</strong></span>
                                    <span>Gastos hoy: <strong class="text-danger">-{{ number_format($b['expenses_today'], 2) }}</strong></span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Filter Area -->
        <div class="col-xl-12 col-lg-12 col-md-12 col-12 mb-4">
            <div class="card border-0 shadow-sm rounded">
                <div class="card-body p-3">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <div class="form-group mb-0">
                                <label class="font-weight-bold f-12 text-dark">BANCO A ANALIZAR</label>
                                <select wire:model.live="selectedBankId" class="form-control form-control-sm">
                                    <option value="all">Todos los Bancos (Global)</option>
                                    @foreach($trackedBanks as $bank)
                                        <option value="{{ $bank->id }}">{{ $bank->name }} ({{ $bank->currency_code }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group mb-0">
                                <label class="font-weight-bold f-12 text-dark">DESDE</label>
                                <input type="date" wire:model.live="dateFrom" class="form-control form-control-sm">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group mb-0">
                                <label class="font-weight-bold f-12 text-dark">HASTA</label>
                                <input type="date" wire:model.live="dateTo" class="form-control form-control-sm">
                            </div>
                        </div>
                        <div class="col-md-2 pt-4">
                            <span class="badge badge-light p-2 border font-weight-bold text-dark w-100 text-center" title="Total de días del período seleccionado para el análisis.">
                                {{ \Carbon\Carbon::parse($dateFrom)->diffInDays(\Carbon\Carbon::parse($dateTo)) }} días
                            </span>
                        </div>
                        <div class="col-md-4 text-right pt-3">
                            <button type="button" class="btn btn-info btn-sm font-weight-bold text-white mr-1" wire:click="toggleInterpretationModal">
                                <i class="fas fa-brain"></i> Analizar (IA)
                            </button>
                            <button type="button" class="btn btn-danger btn-sm font-weight-bold text-white" wire:click="openPdfPreview">
                                <i class="fas fa-file-pdf"></i> Exportar PDF
                            </button>
                        </div>
                    </div> 
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="col-xl-12 col-lg-12 col-md-12 col-12 mb-3">
            <ul class="nav nav-pills" style="gap: 4px;">
                <li class="nav-item">
                    <button class="nav-link btn-sm {{ $activeTab == 'dashboard' ? 'active' : '' }}" wire:click.prevent="$set('activeTab', 'dashboard')">
                        <i class="fas fa-chart-line"></i> Dashboard de Análisis
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link btn-sm {{ $activeTab == 'expenses' ? 'active' : '' }}" wire:click.prevent="$set('activeTab', 'expenses')">
                        <i class="fas fa-receipt"></i> Historial de Gastos
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link btn-sm {{ $activeTab == 'transfers' ? 'active' : '' }}" wire:click.prevent="$set('activeTab', 'transfers')">
                        <i class="fas fa-exchange-alt"></i> Transferencias Internas
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link btn-sm {{ $activeTab == 'closures' ? 'active' : '' }}" wire:click.prevent="$set('activeTab', 'closures')">
                        <i class="fas fa-lock"></i> Cortes Diarios Realizados
                    </button>
                </li>
            </ul>
        </div>

        <!-- Tab Content -->
        <div class="col-xl-12 col-lg-12 col-md-12 col-12">
            <!-- TAB: DASHBOARD -->
            @if($activeTab === 'dashboard')
                <div class="row">
                    <!-- Highcharts: Expenses Category Distribution -->
                    <div class="col-md-6 col-12 mb-4">
                        <div class="card border-0 shadow-sm h-100 rounded">
                            <div class="card-header bg-white border-bottom-0 pt-3">
                                <h5 class="card-title font-weight-bold text-dark mb-0"><i class="fas fa-chart-pie text-danger"></i> Distribución de Gastos</h5>
                            </div>
                            <div class="card-body"
                                 data-series="{{ json_encode($chartData['categories'] ?? []) }}"
                                 data-currency="{{ $chartData['currency_code'] ?? 'USD' }}"
                                 x-data="{
                                     render() {
                                         const seriesData = JSON.parse(this.$el.getAttribute('data-series') || '[]');
                                         const currency = this.$el.getAttribute('data-currency') || 'USD';
                                         
                                         Highcharts.chart(this.$refs.categoryChart, {
                                             chart: { type: 'pie', backgroundColor: 'transparent' },
                                             title: { text: '' },
                                             tooltip: {
                                                 pointFormat: '<b>{point.name}</b>: {point.y:.2f} ' + currency + ' ({point.percentage:.1f}%)'
                                             },
                                             accessibility: { point: { valueSuffix: '%' } },
                                             plotOptions: {
                                                 pie: {
                                                     allowPointSelect: true,
                                                     cursor: 'pointer',
                                                     dataLabels: {
                                                         enabled: true,
                                                         format: '<b>{point.name}</b>: {point.percentage:.1f} %'
                                                     }
                                                 }
                                             },
                                             credits: { enabled: false },
                                             series: [{
                                                 name: 'Monto',
                                                 colorByPoint: true,
                                                 data: seriesData
                                             }]
                                         });
                                     }
                                 }"
                                 x-init="render()"
                                 @chart-updated.window="$nextTick(() => render())"
                                 wire:ignore>
                                <div x-ref="categoryChart" style="height: 350px;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Highcharts: Flow Trend (Balance Evolution) -->
                    <div class="col-md-6 col-12 mb-4">
                        <div class="card border-0 shadow-sm h-100 rounded">
                            <div class="card-header bg-white border-bottom-0 pt-3">
                                <h5 class="card-title font-weight-bold text-dark mb-0"><i class="fas fa-chart-line text-success"></i> Evolución de Saldos de Cierre</h5>
                            </div>
                            <div class="card-body"
                                 data-labels="{{ json_encode($chartData['trend_labels'] ?? []) }}"
                                 data-values="{{ json_encode($chartData['trend_values'] ?? []) }}"
                                 data-currency="{{ $chartData['currency_code'] ?? 'USD' }}"
                                 x-data="{
                                     render() {
                                         const labels = JSON.parse(this.$el.getAttribute('data-labels') || '[]');
                                         const values = JSON.parse(this.$el.getAttribute('data-values') || '[]').map(Number);
                                         const currency = this.$el.getAttribute('data-currency') || 'USD';
                                         
                                         Highcharts.chart(this.$refs.trendChart, {
                                             chart: { type: 'area', backgroundColor: 'transparent' },
                                             title: { text: '' },
                                             xAxis: { categories: labels },
                                             yAxis: { title: { text: 'Saldo (' + currency + ')' } },
                                             tooltip: { valueDecimals: 2, valueSuffix: ' ' + currency },
                                             credits: { enabled: false },
                                             series: [{
                                                 name: 'Saldo de Cierre',
                                                 data: values,
                                                 color: '#28a745',
                                                 fillColor: {
                                                     linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
                                                     stops: [
                                                         [0, 'rgba(40, 167, 69, 0.4)'],
                                                         [1, 'rgba(40, 167, 69, 0)']
                                                     ]
                                                 }
                                             }]
                                         });
                                     }
                                 }"
                                 x-init="render()"
                                 @chart-updated.window="$nextTick(() => render())"
                                 wire:ignore>
                                <div x-ref="trendChart" style="height: 350px;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Highcharts: Essential vs Discretionary -->
                    <div class="col-md-4 col-12 mb-4">
                        <div class="card border-0 shadow-sm h-100 rounded">
                            <div class="card-header bg-white border-bottom-0 pt-3">
                                <h5 class="card-title font-weight-bold text-dark mb-0"><i class="fas fa-tasks text-warning"></i> Necesidad de Gasto</h5>
                            </div>
                            <div class="card-body"
                                 data-series="{{ json_encode($chartData['essential'] ?? []) }}"
                                 x-data="{
                                     render() {
                                         const seriesData = JSON.parse(this.$el.getAttribute('data-series') || '[]');
                                         
                                         Highcharts.chart(this.$refs.essentialChart, {
                                             chart: { type: 'pie', backgroundColor: 'transparent' },
                                             title: { text: '' },
                                             tooltip: {
                                                 pointFormat: '<b>{point.name}</b>: {point.y:.2f} ({point.percentage:.1f}%)'
                                             },
                                             credits: { enabled: false },
                                             plotOptions: {
                                                 pie: {
                                                     dataLabels: {
                                                         enabled: true,
                                                         format: '{point.name}: {point.percentage:.1f}%'
                                                     }
                                                 }
                                             },
                                             series: [{
                                                 name: 'Clasificación',
                                                 colorByPoint: true,
                                                 data: seriesData
                                             }]
                                         });
                                     }
                                 }"
                                 x-init="render()"
                                 @chart-updated.window="$nextTick(() => render())"
                                 wire:ignore>
                                <div x-ref="essentialChart" style="height: 250px;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Combined Movements Table (Top 50) -->
                    <div class="col-md-8 col-12 mb-4">
                        <div class="card border-0 shadow-sm h-100 rounded">
                            <div class="card-header bg-white pt-3 d-flex justify-content-between align-items-center">
                                <h5 class="card-title font-weight-bold text-dark mb-0"><i class="fas fa-list text-muted"></i> Últimos Movimientos de Cuenta</h5>
                                <span class="badge badge-light text-muted border">Top 50 registros</span>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                    <table class="table table-hover table-striped mb-0">
                                        <thead class="thead-dark position-sticky" style="top: 0; z-index: 1;">
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Banco</th>
                                                <th>Tipo</th>
                                                <th>Detalle / Categoría</th>
                                                <th>Referencia</th>
                                                <th class="text-right text-success">DEBE (+)</th>
                                                <th class="text-right text-danger">HABER (-)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($movements as $m)
                                                <tr>
                                                    <td>{{ \Carbon\Carbon::parse($m->date)->format('d/m/Y') }}</td>
                                                    <td><strong>{{ $m->bank_name }}</strong></td>
                                                    <td>
                                                        @if($m->type === 'INGRESO')
                                                            <span class="badge badge-success text-white"><i class="fas fa-arrow-up"></i> Ingreso</span>
                                                        @elseif($m->type === 'GASTO')
                                                            <span class="badge badge-danger text-white"><i class="fas fa-arrow-down"></i> Gasto</span>
                                                        @elseif($m->type === 'TRANSFER_IN')
                                                            <span class="badge badge-primary text-white"><i class="fas fa-exchange-alt"></i> Transf. In</span>
                                                        @elseif($m->type === 'TRANSFER_OUT')
                                                            <span class="badge badge-secondary text-white"><i class="fas fa-exchange-alt"></i> Transf. Out</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($m->category_name)
                                                            <span class="badge p-1 text-white font-weight-bold" style="background-color: {{ $m->category_color ?? '#858796' }}">
                                                                <i class="fas {{ $m->category_icon ?? 'fa-receipt' }}"></i> {{ $m->category_name }}
                                                            </span>
                                                            <div class="text-muted small mt-1">{{ $m->description }}</div>
                                                        @else
                                                            <span class="text-dark">{{ $m->description }}</span>
                                                        @endif
                                                    </td>
                                                    <td><code class="text-primary">{{ $m->reference ?: 'N/A' }}</code></td>
                                                    <td class="text-right font-weight-bold text-success">
                                                        @if($m->type === 'INGRESO' || $m->type === 'TRANSFER_IN')
                                                            +${{ number_format($m->amount, 2) }}
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td class="text-right font-weight-bold text-danger">
                                                        @if($m->type === 'GASTO' || $m->type === 'TRANSFER_OUT')
                                                            -${{ number_format($m->amount, 2) }}
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="text-center p-4 text-muted">No se encontraron movimientos en el rango seleccionado.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- TAB: EXPENSES HISTORIAL -->
            @if($activeTab === 'expenses')
                <div class="card border-0 shadow-sm rounded">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Banco</th>
                                        <th>Categoría</th>
                                        <th>Beneficiario</th>
                                        <th>Referencia</th>
                                        <th>Descripción</th>
                                        <th>Recibo</th>
                                        <th class="text-right">Monto</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($expensesList as $exp)
                                        <tr>
                                            <td>{{ $exp->expense_date->format('d/m/Y') }}</td>
                                            <td><strong>{{ $exp->bank->name }}</strong></td>
                                            <td>
                                                <span class="badge text-white p-1" style="background-color: {{ $exp->category->color }}">
                                                    <i class="fas {{ $exp->category->icon }}"></i> {{ $exp->category->name }}
                                                </span>
                                            </td>
                                            <td>{{ $exp->beneficiary ?: 'N/A' }}</td>
                                            <td><code>{{ $exp->reference ?: 'N/A' }}</code></td>
                                            <td>{{ $exp->description ?: 'N/A' }}</td>
                                            <td>
                                                @if($exp->receipt_path)
                                                    <a href="{{ asset('storage/' . $exp->receipt_path) }}" target="_blank" class="btn btn-sm btn-info p-1">
                                                        <i class="fas fa-image"></i> Ver Recibo
                                                    </a>
                                                @else
                                                    <span class="text-muted small">Sin Recibo</span>
                                                @endif
                                            </td>
                                            <td class="text-right font-weight-bold text-danger">-${{ number_format($exp->amount, 2) }}</td>
                                            <td class="text-center">
                                                @can('treasury.config')
                                                    <button wire:click="deleteExpense({{ $exp->id }})" wire:confirm="¿Estás seguro de eliminar este gasto? El saldo del banco se recalculará." class="btn btn-danger btn-xs p-1" title="Eliminar gasto">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                @else
                                                    <span class="badge badge-light text-muted" style="font-size: 10px;" title="Solo un supervisor puede eliminar gastos"><i class="fas fa-lock text-muted mr-1"></i> Bloqueado</span>
                                                @endcan
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center p-4 text-muted">No hay gastos registrados en este período.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3">
                            {{ $expensesList->links() }}
                        </div>
                    </div>
                </div>
            @endif

            <!-- TAB: TRANSFERS -->
            @if($activeTab === 'transfers')
                <div class="card border-0 shadow-sm rounded">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Banco Origen</th>
                                        <th>Banco Destino</th>
                                        <th class="text-right">Monto Origen</th>
                                        <th class="text-right">Monto Destino</th>
                                        <th class="text-center">Tasa Cambio</th>
                                        <th>Referencia</th>
                                        <th>Notas</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($transfersList as $tr)
                                        <tr>
                                            <td>{{ $tr->transfer_date->format('d/m/Y') }}</td>
                                            <td class="text-danger"><strong>{{ $tr->fromBank->name }}</strong></td>
                                            <td class="text-success"><strong>{{ $tr->toBank->name }}</strong></td>
                                            <td class="text-right font-weight-bold text-danger">-${{ number_format($tr->amount_from, 2) }} {{ $tr->fromBank->currency_code }}</td>
                                            <td class="text-right font-weight-bold text-success">+${{ number_format($tr->amount_to, 2) }} {{ $tr->toBank->currency_code }}</td>
                                            <td class="text-center">{{ number_format($tr->exchange_rate, 4) }}</td>
                                            <td><code>{{ $tr->reference ?: 'N/A' }}</code></td>
                                            <td>{{ $tr->notes ?: 'N/A' }}</td>
                                            <td class="text-center">
                                                @can('treasury.config')
                                                    <button wire:click="deleteTransfer({{ $tr->id }})" wire:confirm="¿Estás seguro de deshacer esta transferencia? Ambos saldos se recalcularán." class="btn btn-danger btn-xs p-1" title="Eliminar transferencia">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                @else
                                                    <span class="badge badge-light text-muted" style="font-size: 10px;" title="Solo un supervisor puede eliminar transferencias"><i class="fas fa-lock text-muted mr-1"></i> Bloqueado</span>
                                                @endcan
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center p-4 text-muted">No hay transferencias registradas en este período.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3">
                            {{ $transfersList->links() }}
                        </div>
                    </div>
                </div>
            @endif

            <!-- TAB: DAILY CLOSURES -->
            @if($activeTab === 'closures')
                <div class="card border-0 shadow-sm rounded">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Fecha Corte</th>
                                        <th>Banco</th>
                                        <th class="text-right">Apertura (Sist / Real)</th>
                                        <th class="text-right">Total Ingresos</th>
                                        <th class="text-right">Total Gastos</th>
                                        <th class="text-right">Cierre (Sist / Real)</th>
                                        <th class="text-center">Comprobante Banco</th>
                                        <th class="text-center">Arqueo (Diferencia)</th>
                                        <th>Ejecutado por</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($closuresList as $cls)
                                        <tr>
                                            <td><strong>{{ $cls->closure_date->format('d/m/Y') }}</strong></td>
                                            <td>{{ $cls->bank->name }}</td>
                                            <td class="text-right">
                                                <div>Sist: ${{ number_format($cls->opening_balance, 2) }}</div>
                                                @if($cls->manual_opening_balance !== null)
                                                    <small class="text-info font-weight-bold">Real: ${{ number_format($cls->manual_opening_balance, 2) }}</small>
                                                @endif
                                            </td>
                                            <td class="text-right text-success">+${{ number_format($cls->total_income, 2) }} ({{ $cls->total_income_count }})</td>
                                            <td class="text-right text-danger">-${{ number_format($cls->total_expenses, 2) }} ({{ $cls->total_expenses_count }})</td>
                                            <td class="text-right">
                                                <div class="font-weight-bold text-dark">Sist: ${{ number_format($cls->closing_balance, 2) }}</div>
                                                @if($cls->manual_closing_balance !== null)
                                                    <small class="text-primary font-weight-bold">Real: ${{ number_format($cls->manual_closing_balance, 2) }}</small>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($cls->closing_proof_image)
                                                    <a href="{{ asset('storage/' . $cls->closing_proof_image) }}" target="_blank" class="btn btn-outline-info btn-xs p-1" title="Ver captura del banco">
                                                        <i class="fas fa-image"></i> Ver Capture
                                                    </a>
                                                @else
                                                    <span class="text-muted small">Sin imagen</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($cls->manual_closing_balance !== null)
                                                    @if(abs($cls->closing_difference) < 0.01)
                                                        <span class="badge badge-success"><i class="fas fa-check-circle"></i> Cuadre OK</span>
                                                    @elseif($cls->closing_difference > 0)
                                                        <span class="badge badge-warning" title="Sobrante en banco real de +${{ number_format($cls->closing_difference, 2) }}">
                                                            <i class="fas fa-arrow-up"></i> +${{ number_format($cls->closing_difference, 2) }} (Sobrante)
                                                        </span>
                                                    @else
                                                        <span class="badge badge-danger" title="Faltante en banco real de -${{ number_format(abs($cls->closing_difference), 2) }}">
                                                            <i class="fas fa-arrow-down"></i> -${{ number_format(abs($cls->closing_difference), 2) }} (Faltante)
                                                        </span>
                                                    @endif
                                                @else
                                                    <span class="badge badge-light text-muted">Auto (Sistema)</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div>{{ $cls->closedBy ? $cls->closedBy->name : 'Sistema (Auto)' }}</div>
                                                @if($cls->notes)
                                                    <small class="text-muted d-block">{{ $cls->notes }}</small>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                 <a href="{{ route('reports.bank.treasury.pdf', ['bank_id' => $cls->bank_id, 'date_from' => $cls->closure_date->format('Y-m-d'), 'date_to' => $cls->closure_date->format('Y-m-d'), 'type' => 'dashboard']) }}" target="_blank" class="btn btn-danger btn-xs px-2 py-1 mr-1 shadow-sm font-weight-bold" title="Ver / Imprimir Reporte PDF de Movimientos del Día">
                                                     <i class="fas fa-file-pdf"></i> PDF
                                                 </a>
                                                @can('treasury.config')
                                                    <button wire:click="deleteClosure({{ $cls->id }})" wire:confirm="¿Estás seguro de eliminar este corte diario? Saldo actual se mantendrá pero el historial del corte se borrará." class="btn btn-outline-danger btn-xs p-1" title="Eliminar corte diario">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                @else
                                                    <span class="badge badge-light text-muted" style="font-size: 10px;" title="Solo un supervisor puede eliminar cortes diarios"><i class="fas fa-lock text-muted mr-1"></i> Bloqueado</span>
                                                @endcan
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center p-4 text-muted">No hay cortes diarios registrados en este período.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3">
                            {{ $closuresList->links() }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- AI Interpretation Modal --}}
    @if($showInterpretationModal)
    <div class="modal show d-block" style="background: rgba(0,0,0,0.6); z-index: 9999;" tabindex="-1" role="dialog" wire:key="interpretation-modal">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title text-white font-weight-bold"><i class="fas fa-brain mr-2 text-info"></i> Analizador Inteligente de Gastos y Flujos</h5>
                    <button type="button" class="close text-white" wire:click="toggleInterpretationModal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4 bg-white" style="max-height: 70vh; overflow-y: auto;">
                    {!! $this->getInterpretation() !!}
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" wire:click="toggleInterpretationModal"><i class="fas fa-times mr-1"></i> Cerrar Análisis</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- PDF Viewer in Modal --}}
    @if($showPdfModal)
    <div class="modal fade show" style="display: block; background: rgba(0,0,0,0.7); z-index: 9999;" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document" style="height: 90vh; max-width: 95vw; margin-top: 5vh;">
            <div class="modal-content" style="height: 100%;">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title text-white">Vista Previa: Reporte de Tesorería y Auditoría Bancaria</h5>
                    <button type="button" class="close text-white" wire:click="closePdfPreview" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0" style="height: calc(100% - 60px);">
                    @if($pdfUrl)
                        <iframe src="{{ $pdfUrl }}" style="width: 100%; height: 100%; border: none;"></iframe>
                    @else
                        <div class="d-flex justify-content-center align-items-center" style="height: 100%;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Cargando...</span>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closePdfPreview">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
