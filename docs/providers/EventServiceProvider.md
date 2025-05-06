# EventServiceProvider

## 🎯 Əsas Məqsəd

* Laravel event və listener-ların qeydiyyatını və idarə edilməsini təmin edir
* Event-driven development üçün mərkəzi konfiqurasiya nöqtəsi təmin edir
* Sistemdəki hadisələrin izlənməsi və müvafiq reaksiyaların təşkili

## 🚀 Sürətli Başlanğıc

~~~php
// app/Providers/EventServiceProvider.php
protected $listen = [
    Registered::class => [
        SendWelcomeEmail::class,
    ],
    OrderCreated::class => [
        SendOrderConfirmation::class,
        UpdateInventory::class,
    ],
];
~~~

## 📋 Əsas İstifadə Halları

### 1. Event və Listener Qeydiyyatı

~~~php
protected $listen = [
    // Qeydiyyat eventi
    Registered::class => [
        SendWelcomeEmail::class,
        CreateUserProfile::class,
    ],
    
    // Sifariş eventi
    OrderPlaced::class => [
        SendOrderNotification::class,
        UpdateStockLevel::class,
    ],
];
~~~

* Sistemdə baş verən hadisələrin izlənməsi üçün
* Asinxron əməliyyatların idarə edilməsi üçün

### 2. Custom Event Yaratmaq

~~~php
// Event class
class OrderCreated
{
    public $order;
    
    public function __construct($order)
    {
        $this->order = $order;
    }
}

// Listener class
class SendOrderConfirmation
{
    public function handle(OrderCreated $event)
    {
        Mail::to($event->order->email)->send(new OrderConfirmationMail($event->order));
    }
}
~~~

* Xüsusi business logic hadisələri üçün
* Sistemdə modifikasiya tələb edən proseslər üçün

## 🔧 Konfiqurasiya Detalları

### Event Registration

* ✨ Event və listener cütlüklərini qeydiyyata alır
* ⚡️ Nümunə:

~~~php
protected $listen = [
    'App\Events\UserRegistered' => [
        'App\Listeners\SendWelcomeEmail',
        'App\Listeners\CreateUserSettings',
    ],
];
~~~

### Auto-Discovery

* ✨ Event və listener-ların avtomatik aşkarlanması
* ⚡️ Nümunə:

~~~php
public function shouldDiscoverEvents(): bool
{
    return false; // Manual qeydiyyata üstünlük verilir
}
~~~

## ⚠️ Vacib Qeydlər

* Event-lər FIFO (First In First Out) prinsipi ilə işləyir
* Listener-lər asinxron ola bilər (ShouldQueue interface)
* Auto-discovery default olaraq deaktivdir
* Event və listener-lər arasında 1:N əlaqəsi var

## 🔗 Əlaqəli Komponentlər

* `Event` class-ları
* `Listener` class-ları
* `ShouldQueue` interface
* Notification sistemi

## 💡 Event/Listener Nümunələri

### Authentication Events
~~~php
protected $listen = [
    Registered::class => [
        SendWelcomeEmail::class,
    ],
    Login::class => [
        LogSuccessfulLogin::class,
    ],
    Failed::class => [
        NotifySecurityTeam::class,
    ],
];
~~~

### Business Logic Events
~~~php
protected $listen = [
    OrderCreated::class => [
        SendOrderConfirmation::class,
        UpdateInventory::class,
        NotifyAdministrator::class,
    ],
    PaymentReceived::class => [
        UpdateOrderStatus::class,
        SendPaymentReceipt::class,
    ],
];
~~~

## 📝 Listener Nümunəsi

~~~php
class SendWelcomeEmail
{
    public function handle(Registered $event)
    {
        $user = $event->user;
        
        Mail::to($user->email)->send(new WelcomeEmail($user));
    }
}
~~~

## 🔄 Event Dispatch Nümunəsi

~~~php
// Controller və ya Service-də
class OrderController
{
    public function store(Request $request)
    {
        $order = Order::create($request->validated());
        
        event(new OrderCreated($order));
        // və ya
        OrderCreated::dispatch($order);
    }
}
~~~
