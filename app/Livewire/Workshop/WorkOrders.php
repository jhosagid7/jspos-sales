<?php

namespace App\Livewire\Workshop;

use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Services\WorkOrderBillingService;
use Livewire\Component;
use Livewire\WithPagination;

class WorkOrders extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public ?int $selectedOrderId = null;

    public array $newOrder = [
        'customer_id' => null,
        'vehicle_id' => null,
        'mechanic_id' => null,
        'entry_mileage' => null,
        'fuel_level' => '1/2',
        'initial_diagnosis' => '',
        'promised_at' => null,
    ];

    public array $newItem = [
        'type' => 'labor',
        'description' => '',
        'quantity' => 1,
        'unit_price' => 0.00,
        'unit_cost' => 0.00,
        'product_id' => null,
        'mechanic_id' => null,
        'mechanic_commission_percentage' => 0.00,
    ];

    public array $billingData = [
        'payment_method' => 'cash_usd',
        'exit_mileage' => null,
        'notes' => '',
    ];

    public bool $showCreateModal = false;
    public bool $showItemModal = false;
    public bool $showBillingModal = false;

    protected function rules(): array
    {
        return [
            'newOrder.customer_id' => 'required|exists:customers,id',
            'newOrder.vehicle_id' => 'required|exists:vehicles,id',
            'newOrder.entry_mileage' => 'required|integer|min:0',
            'newOrder.initial_diagnosis' => 'required|string|min:3|max:2000',
            'newOrder.fuel_level' => 'nullable|string|max:20',
            'newOrder.mechanic_id' => 'nullable|exists:users,id',
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedNewOrderCustomerId($customerId): void
    {
        $this->newOrder['vehicle_id'] = null;
    }

    public function selectOrder(int $orderId): void
    {
        $this->selectedOrderId = $orderId;
        $order = WorkOrder::find($orderId);
        if ($order && empty($this->billingData['exit_mileage'])) {
            $this->billingData['exit_mileage'] = $order->entry_mileage;
        }
    }

    public function storeOrder(): void
    {
        $this->validate();

        $userId = auth()->id() ?: User::first()?->id ?: 1;

        $order = WorkOrder::create([
            'customer_id' => $this->newOrder['customer_id'],
            'vehicle_id' => $this->newOrder['vehicle_id'],
            'user_id' => $userId,
            'mechanic_id' => $this->newOrder['mechanic_id'] ?: null,
            'entry_mileage' => (int)$this->newOrder['entry_mileage'],
            'fuel_level' => $this->newOrder['fuel_level'] ?: '1/2',
            'initial_diagnosis' => $this->newOrder['initial_diagnosis'],
            'status' => 'reception',
        ]);

        $this->newOrder = [
            'customer_id' => null,
            'vehicle_id' => null,
            'mechanic_id' => null,
            'entry_mileage' => null,
            'fuel_level' => '1/2',
            'initial_diagnosis' => '',
            'promised_at' => null,
        ];

        $this->showCreateModal = false;
        $this->selectedOrderId = $order->id;
        $this->dispatch('order-created', orderId: $order->id);
    }

    public function addItem(): void
    {
        if (!$this->selectedOrderId) {
            return;
        }

        $this->validate([
            'newItem.description' => 'required|string|min:2|max:255',
            'newItem.quantity' => 'required|numeric|min:0.01',
            'newItem.unit_price' => 'required|numeric|min:0',
            'newItem.type' => 'required|in:part,labor',
            'newItem.mechanic_id' => 'nullable|exists:users,id',
            'newItem.mechanic_commission_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        $order = WorkOrder::findOrFail($this->selectedOrderId);
        $order->addItem($this->newItem);

        $this->newItem = [
            'type' => 'labor',
            'description' => '',
            'quantity' => 1,
            'unit_price' => 0.00,
            'unit_cost' => 0.00,
            'product_id' => null,
            'mechanic_id' => null,
            'mechanic_commission_percentage' => 0.00,
        ];

        $this->showItemModal = false;
        $this->dispatch('item-added');
    }

    public function changeStatus(int $orderId, string $newStatus): void
    {
        $order = WorkOrder::findOrFail($orderId);
        $order->updateStatus($newStatus);
        $this->dispatch('status-updated', orderId: $orderId, status: $newStatus);
    }

    public function createWorkOrder(): void
    {
        $this->storeOrder();
    }

    public function billOrder(?int $orderId = null, ?string $paymentMethod = null, ?int $exitMileage = null): void
    {
        $targetId = $orderId ?: $this->selectedOrderId;
        if (!$targetId) {
            return;
        }

        $order = WorkOrder::findOrFail($targetId);

        $service = app(WorkOrderBillingService::class);
        $userId = auth()->id() ?: $order->user_id;

        $effectivePaymentMethod = $paymentMethod ?: ($this->billingData['payment_method'] ?? 'cash_usd');
        $effectiveExitMileage = !is_null($exitMileage)
            ? (int)$exitMileage
            : (!empty($this->billingData['exit_mileage']) ? (int)$this->billingData['exit_mileage'] : $order->entry_mileage);

        $service->bill($order, [
            'user_id' => $userId,
            'payment_method' => $effectivePaymentMethod,
            'exit_mileage' => $effectiveExitMileage,
            'notes' => $this->billingData['notes'] ?? '',
        ]);

        $this->showBillingModal = false;
        $this->dispatch('order-billed', orderId: $targetId);
    }

    public function onProductSelected($productId): void
    {
        if (!$productId) {
            return;
        }

        $product = Product::find($productId);
        if ($product) {
            $this->newItem['product_id'] = $product->id;
            $this->newItem['description'] = $product->name;
            $this->newItem['unit_price'] = (float)$product->price;
            $this->newItem['unit_cost'] = (float)$product->cost;
        }
    }

    public function render()
    {
        $query = WorkOrder::with(['customer', 'vehicle', 'mechanic', 'items'])
            ->search($this->search);

        if (!empty($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        $orders = $query->latest()->paginate(10);

        $selectedOrder = $this->selectedOrderId
            ? WorkOrder::with(['customer', 'vehicle', 'mechanic', 'items.mechanic', 'items.product'])->find($this->selectedOrderId)
            : null;

        $customers = Customer::orderBy('name')->take(50)->get();

        $vehicles = !empty($this->newOrder['customer_id'])
            ? Vehicle::where('customer_id', $this->newOrder['customer_id'])->get()
            : collect();

        $mechanics = User::all();

        $products = Product::where('status', 'available')->orderBy('name')->take(100)->get();

        return view('livewire.workshop.work-orders', [
            'orders' => $orders,
            'selectedOrder' => $selectedOrder,
            'customers' => $customers,
            'vehicles' => $vehicles,
            'mechanics' => $mechanics,
            'products' => $products,
        ]);
    }
}
