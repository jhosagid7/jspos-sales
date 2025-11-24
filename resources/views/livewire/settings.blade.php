<div>
    <div class="row">
        <div class="col-md-6">
            <div class="card card-absolute">
                <div class="card-header bg-primary">
                    <h5 class="txt-light">Configuraciones Generales</h5>
                </div>

                <div class="card-body">

                    <div class="row">

                        <div class="form-group col-sm-12 col-md-6">
                            <span>EMPRESA<span class="txt-danger">*</span></span>
                            <input wire:model="businessName" id='inputFocus' type="text"
                                class="form-control text-purple" placeholder="nombre" maxlength="150">
                            @error('businessName')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>


                        <div class="form-group col-sm-12 col-md-6">
                            <span>TELÉFONO <span class="txt-danger"></span></span>
                            <input wire:model="phone" type="text" class="form-control text-purple " placeholder=""
                                maxlength="20">
                            @error('phone')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-3 row">
                        <div class="form-group col-sm-12 col-md-6">
                            <span>CC / NIT <span class="txt-danger">*</span></span>
                            <input wire:model="taxpayerId" type="text" class="form-control text-purple "
                                placeholder="" maxlength="35">
                            @error('taxpayerId')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group col-sm-12 col-md-3">
                            <span>IVA / VAT <span class="txt-danger">*</span></span>
                            <input wire:model="vat" type="text" class="form-control text-purple " placeholder="">
                            @error('vat')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group col-sm-12 col-md-3">
                            <span>n° de Decimales <span class="txt-danger">*</span></span>
                            <input wire:model="decimals" type="text" class="form-control text-purple "
                                placeholder="">
                            @error('decimals')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-3 row">
                        <div class="form-group col-sm-12 col-md-6">
                            <span>IMPRESORA<span class="txt-danger">*</span></span>
                            <input wire:model="printerName" type="text" class="form-control text-purple "
                                placeholder="" maxlength="55">
                            @error('printerName')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>



                        <div class="form-group col-sm-12 col-md-6">
                            <span>VENTAS CRÉDITO (DÍAS)<span class="txt-danger">*</span></span>
                            <input wire:model="creditDays" type="number" class="form-control text-purple "
                                placeholder="">
                            @error('creditDays')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-3 row">
                        <div class="form-group col-sm-12 col-md-6">
                            <span>WEBSITE<span class="txt-danger"></span></span>
                            <input wire:model="website" type="text" class="form-control text-purple "
                                placeholder="www.website.com" maxlength="99">
                            @error('website')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group col-sm-12 col-md-6">
                            <span>LEYENDA<span class="txt-danger"></span></span>
                            <input wire:model="leyend" type="text" class="form-control text-purple" placeholder=""
                                maxlength="99">
                            @error('leyend')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-3 row">
                        <div class="form-group col-sm-12 col-md-6">
                            <span class="form-label">CITY <span class="txt-danger"></span></span>
                            <input wire:model="city" class="form-control text-purple" type="text" maxlength="255">
                            @error('city')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group col-sm-12 col-md-6">
                            <span>DIRECCIÓN<span class="txt-danger"></span></span>
                            <textarea wire:model="address" class="form-control text-purple" cols="30" rows="2"></textarea>

                            @error('address')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror

                        </div>
                    </div>
                    <div class="mt-3 row">
                        <div class="form-group col-sm-12 col-md-6">
                            <span class="form-label">COMPRAS CRÉDITO (DÍAS)* <span class="txt-danger"></span></span>
                            <input wire:model="creditPurchaseDays" class="form-control text-purple" type="text"
                                maxlength="255">
                            @error('city')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group col-sm-12 col-md-6">
                            <span>CODIGO DE CONFIRMACION<span class="txt-danger"></span></span>
                            <textarea wire:model="confirmationCode" class="form-control text-purple" cols="30" rows="2"></textarea>

                            @error('address')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror

                        </div>
                        <div class="form-group col-sm-12 col-md-6">
                            <label for="primaryCurrency">MONEDA PRINIPAL</label>
                            <select wire:model="primaryCurrency" class="form-control text-purple">
                                <option value="">Seleccione una moneda</option>
                                @foreach ($currencies as $currency)
                                    <option value="{{ $currency->code }}"
                                        {{ $currency->code == $primaryCurrency ? 'selected' : '' }}>
                                        {{ $currency->code }} ({{ $currency->label }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="mt-3 form-group col-sm-12 col-md-6">
                                <button wire:click="setPrimaryCurrency" class="btn btn-primary ">Guardar Moneda
                                    Principal</button>
                            </div>
                        </div>


                        <div class="form-group col-sm-12 col-md-6 text-purple">
                            <h5>Agregar Moneda Secundaria</h5>
                            <input wire:model="newCurrencyCode" type="text" class="form-control text-purple"
                                placeholder="Código (ISO 4217)">
                            <input wire:model="newCurrencyLabel" type="text" class="form-control text-purple"
                                placeholder="Label">
                            <input wire:model="newCurrencySymbol" type="text" class="form-control text-purple"
                                placeholder="Simbolo">
                            <input wire:model="newExchangeRate" type="number" step="0.000001"
                                class="mt-2 form-control" placeholder="Tasa de Cambio">
                            <button wire:click="addCurrency" class="mt-2 btn btn-primary">Agregar</button>
                        </div>

                        <table class="table mt-4">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Label</th>
                                    <th>Simbolo</th>
                                    <th>Tasa de Cambio</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($currencies as $currency)
                                    <tr>
                                        <td>{{ $currency->code }}</td>
                                        <td>{{ $currency->label }}</td>
                                        <td>{{ $currency->symbol }}</td>
                                        <td>{{ $currency->exchange_rate }}</td>
                                        <td>
                                            <button wire:click="deleteCurrency('{{ $currency->id }}')"
                                                class="btn btn-danger btn-sm">Eliminar</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>


                    <div class="p-2 card-footer">

                        <button class="btn btn-info" wire:click.prevent="saveConfig" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="saveConfig">
                                Guardar
                            </span>
                            <span wire:loading wire:target="saveConfig">
                                Registrando...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        <div class="col-md-6">
            <div class="card card-absolute">
                <div class="card-header bg-primary">
                    <h5 class="txt-light">Configuración de Bancos</h5>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Nombre del Banco</label>
                        <input wire:model="newBankName" type="text" class="form-control text-purple" placeholder="Nombre">
                        @error('newBankName') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="mt-2 form-group">
                        <label>Moneda del Banco</label>
                        <select wire:model="newBankCurrency" class="form-control text-purple">
                            <option value="">Seleccione una moneda</option>
                            @foreach ($currencies as $currency)
                                <option value="{{ $currency->code }}">{{ $currency->code }} - {{ $currency->label }}</option>
                            @endforeach
                        </select>
                        @error('newBankCurrency') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <button wire:click="addBank" class="mt-2 btn btn-primary">Agregar Banco</button>

                    <table class="table mt-4">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Moneda</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($banks as $bank)
                                <tr>
                                    <td>{{ $bank->name }}</td>
                                    <td>{{ $bank->currency_code }}</td>
                                    <td>
                                        <button wire:click="deleteBank({{ $bank->id }})" class="btn btn-danger btn-sm">Eliminar</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
