import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'dart:async';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';
import 'dart:io';
import 'dart:math';

void main() {
  runApp(const JSPOSMobile());
}

class JSPOSMobile extends StatelessWidget {
  const JSPOSMobile({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'JSPOS Mobile',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF1A237E), primary: const Color(0xFF1A237E)),
        useMaterial3: true,
      ),
      home: const LoginScreen(),
    );
  }
}

// --- MODELS ---
class Product {
  final int id;
  final String name;
  final String sku;
  final double price;
  final double stock;
  final double reservedStock;
  final double availableStock;
  final bool checkReservation;
  final String imagePath;

  Product({
    required this.id, 
    required this.name, 
    required this.sku, 
    required this.price, 
    required this.stock, 
    this.reservedStock = 0.0,
    this.availableStock = 0.0,
    this.checkReservation = false,
    required this.imagePath
  });

  factory Product.fromJson(Map<String, dynamic> json) {
    return Product(
      id: int.parse(json['id'].toString()),
      name: json['name'],
      sku: json['sku'] ?? 'N/A',
      price: json['price'] != null ? double.parse(json['price'].toString()) : 0.0,
      stock: json['stock'] != null ? double.parse(json['stock'].toString()) : 0.0,
      reservedStock: json['reserved_stock'] != null ? double.parse(json['reserved_stock'].toString()) : 0.0,
      availableStock: json['available_stock'] != null ? double.parse(json['available_stock'].toString()) : 0.0,
      checkReservation: json['check_reservation'] == 1 || json['check_reservation'] == true,
      imagePath: json['image_path'] ?? 'noimage.jpg',
    );
  }
}

class Customer {
  final int id;
  final String name;
  final double totalDebt;
  final int pendingCount;
  final bool hasOverdue;

  Customer({
    required this.id, 
    required this.name, 
    this.totalDebt = 0.0, 
    this.pendingCount = 0, 
    this.hasOverdue = false
  });

  factory Customer.fromJson(Map<String, dynamic> json) => Customer(
    id: int.parse(json['id'].toString()), 
    name: json['name'],
    totalDebt: json['total_debt'] != null ? double.parse(json['total_debt'].toString()) : 0.0,
    pendingCount: json['pending_count'] ?? 0,
    hasOverdue: json['has_overdue'] == true || json['has_overdue'] == 1,
  );
}

class CartItem {
  final Product product;
  double quantity;
  Customer customer;
  CartItem({required this.product, required this.customer, this.quantity = 1.0});
  double get total => product.price * quantity;
}

// --- LOGIN SCREEN ---
class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});
  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _isLoading = false;
  bool _obscurePassword = true;
  String _baseUrl = 'http://192.168.194.66';

  String _deviceToken = "";

  @override
  void initState() { super.initState(); _init(); }
  _init() async { 
    final prefs = await SharedPreferences.getInstance(); 
    
    String token = prefs.getString('device_token') ?? '';
    if (token.isEmpty) {
      token = 'SELL-${DateTime.now().millisecondsSinceEpoch}-${_generateRandomString(4)}';
      await prefs.setString('device_token', token);
    }

    String userToken = prefs.getString('token') ?? '';
    String lastEmail = prefs.getString('last_email') ?? '';

    setState(() { 
      _baseUrl = prefs.getString('base_url') ?? 'http://192.168.194.66'; 
      _emailController.text = lastEmail; 
      _deviceToken = token;
    }); 

    if (userToken.isNotEmpty) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) {
          Navigator.pushReplacement(context, MaterialPageRoute(builder: (context) => const DashboardScreen()));
        }
      });
    }
  }

  String _generateRandomString(int length) {
    const chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    final rand = Random();
    return List.generate(length, (index) => chars[rand.nextInt(chars.length)]).join();
  }

  Future<void> _showSettings() async {
    final controller = TextEditingController(text: _baseUrl);
    await showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Configuración Servidor'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(title: const Text('VPN ZeroTier'), subtitle: const Text('http://192.168.194.66'), onTap: () => controller.text = 'http://192.168.194.66'),
            ListTile(title: const Text('IP Local'), subtitle: const Text('http://192.168.1.100'), onTap: () => controller.text = 'http://192.168.1.100'),
            const Divider(),
            TextField(controller: controller, decoration: const InputDecoration(labelText: 'IP Personalizada')),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('CERRAR')),
          ElevatedButton(onPressed: () async {
            final prefs = await SharedPreferences.getInstance();
            await prefs.setString('base_url', controller.text);
            setState(() => _baseUrl = controller.text);
            if (mounted) Navigator.pop(context);
          }, child: const Text('GUARDAR')),
        ],
      ),
    );
  }

  Future<void> _login() async {
    setState(() => _isLoading = true);
    final prefs = await SharedPreferences.getInstance();
    final String savedToken = prefs.getString('token') ?? '';
    final String lastEmail = prefs.getString('last_email') ?? '';

    try {
      final response = await http.post(
        Uri.parse('$_baseUrl/api/login'),
        headers: {
          'Accept': 'application/json',
          'X-Device-Token': _deviceToken
        },
        body: {'email': _emailController.text, 'password': _passwordController.text, 'device_name': 'Mobile (Seller)'},
      ).timeout(const Duration(seconds: 8));

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        await prefs.setInt('user_id', data['user']['id'] ?? 0);
        await prefs.setString('token', data['token'] ?? '');
        await prefs.setString('user_name', data['user']['name'] ?? 'Vendedor');
        await prefs.setString('last_email', _emailController.text);
        
        // SAVE LOGISTICS INFO
        await prefs.setString('deadline', data['user']['order_deadline_at'] ?? '');
        await prefs.setBool('deadline_active', (data['user']['is_deadline_active'] == 1 || data['user']['is_deadline_active'] == true));

        if (mounted) Navigator.pushReplacement(context, MaterialPageRoute(builder: (context) => const DashboardScreen()));
      } else {
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Credenciales incorrectas')));
      }
    } catch (e) {
      if (savedToken.isNotEmpty && _emailController.text.trim().toLowerCase() == lastEmail.trim().toLowerCase()) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('📱 MODO OFFLINE: Sesión iniciada con datos guardados.'), backgroundColor: Colors.orange)
          );
          Navigator.pushReplacement(context, MaterialPageRoute(builder: (context) => const DashboardScreen()));
        }
      } else {
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Sin conexión al servidor: $e')));
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        width: double.infinity,
        decoration: const BoxDecoration(gradient: LinearGradient(begin: Alignment.topCenter, end: Alignment.bottomCenter, colors: [Color(0xFF1A237E), Color(0xFF0D47A1)])),
        child: Column(
          children: [
            const SizedBox(height: 60),
            Row(mainAxisAlignment: MainAxisAlignment.end, children: [IconButton(onPressed: _showSettings, icon: const Icon(Icons.settings, color: Colors.white70))]),
            const Icon(Icons.shopping_cart_checkout, size: 100, color: Colors.white),
            const Text('JSPOS Sales', style: TextStyle(color: Colors.white, fontSize: 36, fontWeight: FontWeight.bold)),
            const Spacer(),
            Container(
              padding: const EdgeInsets.all(30),
              decoration: const BoxDecoration(color: Colors.white, borderRadius: BorderRadius.vertical(top: Radius.circular(30))),
              child: Column(
                children: [
                  TextField(controller: _emailController, decoration: InputDecoration(labelText: 'Usuario (Email)', border: const OutlineInputBorder(), prefixIcon: const Icon(Icons.person), suffixIcon: IconButton(icon: const Icon(Icons.clear), onPressed: () => _emailController.clear()))),
                  const SizedBox(height: 20),
                  TextField(
                    controller: _passwordController,
                    obscureText: _obscurePassword,
                    decoration: InputDecoration(
                      labelText: 'Contraseña', border: const OutlineInputBorder(), prefixIcon: const Icon(Icons.lock),
                      suffixIcon: IconButton(icon: Icon(_obscurePassword ? Icons.visibility_off : Icons.visibility), onPressed: () => setState(() => _obscurePassword = !_obscurePassword)),
                    ),
                  ),
                  const SizedBox(height: 30),
                  SizedBox(
                    width: double.infinity, height: 55,
                    child: ElevatedButton(
                      onPressed: _isLoading ? null : _login,
                      style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF1A237E), foregroundColor: Colors.white, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10))),
                      child: _isLoading ? const CircularProgressIndicator(color: Colors.white) : const Text('ENTRAR', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                    ),
                  ),
                  const SizedBox(height: 10),
                  Text('Servidor: $_baseUrl', style: const TextStyle(fontSize: 10, color: Colors.grey)),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// --- DASHBOARD ---
class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});
  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  String _userName = "";
  DateTime? _deadline;
  bool _isDeadlineActive = false;
  Timer? _timer;
  String _timeRemaining = "";
  final Set<int> _notifiedMinuteThresholds = {};
  bool _isRefreshing = false;

  @override
  void initState() { super.initState(); _loadUser(); }
  @override
  void dispose() { _timer?.cancel(); super.dispose(); }

  _loadUser() async { 
    final prefs = await SharedPreferences.getInstance(); 
    setState(() { 
      _userName = prefs.getString('user_name') ?? "Vendedor"; 
      String dlStr = prefs.getString('deadline') ?? "";
      if (dlStr.isNotEmpty) _deadline = DateTime.tryParse(dlStr);
      _isDeadlineActive = prefs.getBool('deadline_active') ?? false;
      _notifiedMinuteThresholds.clear();
    }); 
    if (_isDeadlineActive && _deadline != null) {
      _startTimer();
    }
  }

  Future<void> _refreshConfig() async {
    setState(() => _isRefreshing = true);
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');
      final baseUrl = prefs.getString('base_url');
      final response = await http.get(Uri.parse('$baseUrl/api/user'), headers: {
        'Authorization': 'Bearer $token', 
        'Accept': 'application/json',
        'X-Device-Token': prefs.getString('device_token') ?? ''
      }).timeout(const Duration(seconds: 10));
      if (response.statusCode == 200) {
        final user = json.decode(response.body);
        await prefs.setString('deadline', user['order_deadline_at'] ?? '');
        await prefs.setBool('deadline_active', (user['is_deadline_active'] == 1 || user['is_deadline_active'] == true));
        _loadUser();
      }
    } catch (e) { debugPrint("Refresh Err: $e"); }
    finally { setState(() => _isRefreshing = false); }
  }

  void _startTimer() {
    _timer?.cancel();
    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (_deadline == null) return;
      final now = DateTime.now();
      final diff = _deadline!.difference(now);
      
      if (mounted) {
        if (diff.isNegative) {
          setState(() => _timeRemaining = "CERRADO");
          _checkThresholdAlert(0);
        } else {
          setState(() => _timeRemaining = "${diff.inHours}:${(diff.inMinutes % 60).toString().padLeft(2, '0')}:${(diff.inSeconds % 60).toString().padLeft(2, '0')}");
          
          int minutesLeft = diff.inMinutes;
          if (minutesLeft == 60 || minutesLeft == 15 || minutesLeft == 5 || minutesLeft == 1) {
              _checkThresholdAlert(minutesLeft);
          }
        }
      }
    });
  }

  void _checkThresholdAlert(int threshold) {
     if (!_notifiedMinuteThresholds.contains(threshold)) {
        _notifiedMinuteThresholds.add(threshold);
        String msg = "";
        if (threshold == 0) msg = "¡SISTEMA CERRADO! No se reciben más pedidos por hoy.";
        else if (threshold == 1) msg = "¡ÚLTIMO MINUTO! Envía tus órdenes ahora.";
        else msg = "ATENCIÓN: Solo quedan $threshold minutos para el cierre logístico.";

        ScaffoldMessenger.of(context).showSnackBar(
           SnackBar(
             content: Row(children: [const Icon(Icons.warning, color: Colors.white), const SizedBox(width: 10), Expanded(child: Text(msg))]),
             backgroundColor: threshold == 0 ? Colors.black87 : (threshold < 5 ? Colors.red : Colors.orange),
             duration: const Duration(seconds: 5),
             behavior: SnackBarBehavior.floating,
           )
        );
     }
  }

  Future<void> _logout() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('token');
    if (mounted) {
      Navigator.pushReplacement(context, MaterialPageRoute(builder: (context) => const LoginScreen()));
    }
  }

  @override
  Widget build(BuildContext context) {
    bool isExpired = _isDeadlineActive && _deadline != null && DateTime.now().isAfter(_deadline!);

    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FA),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        leading: Builder(builder: (context) => IconButton(icon: const Icon(Icons.menu_open, color: Color(0xFF00B4D8), size: 28), onPressed: () => Scaffold.of(context).openDrawer())),
        title: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Text('JSPOS', style: TextStyle(color: Colors.black, fontWeight: FontWeight.w900, fontSize: 20)),
            Text(' Sales', style: TextStyle(color: const Color(0xFF00B4D8).withOpacity(0.8), fontWeight: FontWeight.w500, fontSize: 20)),
          ],
        ),
        actions: [
          IconButton(icon: const Icon(Icons.notifications_none_rounded, color: Colors.grey), onPressed: () {}),
        ],
      ),
      drawer: Drawer(
        child: Column(
          children: [
            UserAccountsDrawerHeader(
              decoration: const BoxDecoration(color: Color(0xFF00B4D8)),
              accountName: Text(_userName, style: const TextStyle(fontWeight: FontWeight.bold)),
              accountEmail: const Text("Vendedor Autorizado"),
              currentAccountPicture: const CircleAvatar(backgroundColor: Colors.white, child: Icon(Icons.person, color: Color(0xFF00B4D8), size: 40)),
            ),
            ListTile(leading: const Icon(Icons.logout, color: Colors.red), title: const Text('Cerrar Sesión'), onTap: _logout),
          ],
        ),
      ),
      body: RefreshIndicator(
        onRefresh: _refreshConfig,
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Welcome Banner inspired by 'Su Web'
              Container(
                width: double.infinity,
                margin: const EdgeInsets.all(15),
                padding: const EdgeInsets.symmetric(horizontal: 25, vertical: 30),
                decoration: BoxDecoration(
                  gradient: LinearGradient(colors: [const Color(0xFF00B4D8), const Color(0xFF0077B6).withOpacity(0.8)], begin: Alignment.topLeft, end: Alignment.bottomRight),
                  borderRadius: BorderRadius.circular(35),
                  boxShadow: [BoxShadow(color: const Color(0xFF00B4D8).withOpacity(0.4), blurRadius: 20, offset: const Offset(0, 10))]
                ),
                child: Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                           Text('Bienvenida,', style: TextStyle(color: Colors.white.withOpacity(0.9), fontSize: 16)),
                           const SizedBox(height: 5),
                           Text(_userName, style: const TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.w900, height: 1.1), maxLines: 2, overflow: TextOverflow.ellipsis),
                           const SizedBox(height: 15),
                           Container(
                             padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                             decoration: BoxDecoration(color: Colors.white.withOpacity(0.2), borderRadius: BorderRadius.circular(20)),
                             child: const Text('Ventas Activas 24/7', style: TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold, letterSpacing: 0.5)),
                           )
                        ],
                      ),
                    ),
                    Icon(Icons.auto_awesome, color: Colors.white.withOpacity(0.5), size: 50),
                  ],
                ),
              ),

              const Padding(
                padding: EdgeInsets.symmetric(horizontal: 25, vertical: 10),
                child: Text('Funciones Principales', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: Color(0xFF1B263B))),
              ),

              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 15),
                child: GridView.count(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  crossAxisCount: 2,
                  crossAxisSpacing: 15,
                  mainAxisSpacing: 15,
                  childAspectRatio: 1.2,
                  children: [
                    _menuCard('PRODUCTOS', Icons.inventory_2_rounded, const Color(0xFF00B4D8), () => Navigator.push(context, MaterialPageRoute(builder: (context) => const CatalogScreen()))),
                    _menuCard('HISTORIAL', Icons.receipt_long_rounded, const Color(0xFF2E7D32), () => Navigator.push(context, MaterialPageRoute(builder: (context) => const OrdersScreen()))),
                    _menuCard('COBROS', Icons.payments_rounded, const Color(0xFFF9C74F), () => Navigator.push(context, MaterialPageRoute(builder: (context) => const PaymentCustomersScreen()))),
                    _menuCard('AUDITORÍA', Icons.fact_check_rounded, const Color(0xFF415A77), () => Navigator.push(context, MaterialPageRoute(builder: (context) => const PaymentAuditScreen()))),
                    _menuCard('RENDIMIENTO', Icons.insights_rounded, const Color(0xFF1B263B), () => Navigator.push(context, MaterialPageRoute(builder: (context) => const PerformanceDashboardScreen()))),
                  ],
                ),
              ),
              
              const SizedBox(height: 25),
              if (_isDeadlineActive && _deadline != null) Padding(
                padding: const EdgeInsets.symmetric(horizontal: 15),
                child: Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: isExpired ? Colors.red.shade50 : const Color(0xFFE0FBFC),
                    borderRadius: BorderRadius.circular(30),
                    border: Border.all(color: isExpired ? Colors.red.shade100 : const Color(0xFF98C1D9).withOpacity(0.3))
                  ),
                  child: Row(
                    children: [
                      Icon(isExpired ? Icons.lock_clock : Icons.timer_outlined, color: isExpired ? Colors.red : const Color(0xFF2D6A4F)),
                      const SizedBox(width: 15),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(isExpired ? 'PEDIDOS CERRADOS' : 'SISTEMA DISPONIBLE', style: TextStyle(color: isExpired ? Colors.red.shade800 : const Color(0xFF2D6A4F), fontWeight: FontWeight.w900, fontSize: 12)),
                            Text(isExpired ? 'El periodo de despacho ha culminado.' : 'Envía tus pedidos antes del cierre.', style: TextStyle(color: isExpired ? Colors.red.shade700 : const Color(0xFF40916C), fontSize: 11)),
                          ],
                        ),
                      ),
                      if (!isExpired) Text(_timeRemaining, style: const TextStyle(fontWeight: FontWeight.w900, color: Color(0xFF2D6A4F), fontSize: 16))
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 120), // Placeholder for bottom margin
            ],
          ),
        ),
      ),
      bottomNavigationBar: Container(
        height: 85,
        decoration: BoxDecoration(
          color: Colors.white,
          boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.04), blurRadius: 20, offset: const Offset(0, -5))]
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceAround,
          children: [
            _navIcon(Icons.grid_view_rounded, 'Panel', true),
            _navIcon(Icons.shopping_bag_outlined, 'Ventas', false),
            _navIcon(Icons.person_pin_rounded, 'Perfil', false),
          ],
        ),
      ),
    );
  }

  Widget _navIcon(IconData icon, String label, bool active) {
    return Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        Icon(icon, color: active ? const Color(0xFF00B4D8) : Colors.grey.shade400, size: 26),
        const SizedBox(height: 5),
        Text(label, style: TextStyle(color: active ? const Color(0xFF00B4D8) : Colors.grey.shade400, fontSize: 10, fontWeight: active ? FontWeight.bold : FontWeight.normal))
      ],
    );
  }

  Widget _menuCard(String title, IconData icon, Color color, VoidCallback onTap) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(30),
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(30),
          boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.03), blurRadius: 15, offset: const Offset(0, 8))]
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(color: color.withOpacity(0.12), borderRadius: BorderRadius.circular(18)),
              child: Icon(icon, color: color, size: 28),
            ),
            const SizedBox(height: 10),
            Text(title, style: TextStyle(fontSize: 12, fontWeight: FontWeight.w900, color: Colors.grey.shade800, letterSpacing: 1))
          ],
        ),
      ),
    );
  }
}

// --- CATALOG SCREEN ---
class CatalogScreen extends StatefulWidget {
  final List<CartItem>? initialCart;
  final Customer? initialCustomer;
  final String? initialNotes;
  final int? originalOrderId;

  const CatalogScreen({super.key, this.initialCart, this.initialCustomer, this.initialNotes, this.originalOrderId});

  @override
  State<CatalogScreen> createState() => _CatalogScreenState();
}

class _CatalogScreenState extends State<CatalogScreen> {
  final List<Product> _allProducts = [];
  final List<Product> _products = [];
  final List<Customer> _customers = [];
  final List<CartItem> _cart = [];
  Customer? _selectedCustomer;
  bool _isLoading = false;
  String? _errorMessage;
  final _searchController = TextEditingController();
  final _notesController = TextEditingController();
  String _baseUrl = "";
  DateTime? _deadline;
  bool _isDeadlineActive = false;

  double get _cartTotal => _cart.fold(0, (sum, item) => sum + item.total);

  void _filterProducts(String query) {
    final s = query.trim().toLowerCase();
    setState(() {
      if (s.isEmpty) {
        _products.clear();
        _products.addAll(_allProducts);
      } else {
        final words = s.split(RegExp(r'\s+'));
        _products.clear();
        _products.addAll(_allProducts.where((p) {
          final name = p.name.toLowerCase();
          final sku = p.sku.toLowerCase();
          return words.every((w) => name.contains(w) || sku.contains(w));
        }));
      }
    });
  }

  @override
  void initState() { 
    super.initState(); 
    if (widget.initialCart != null && widget.initialCart!.isNotEmpty) {
       _cart.clear();
       _cart.addAll(widget.initialCart!);
    }
    if (widget.initialCustomer != null) _selectedCustomer = widget.initialCustomer;
    if (widget.initialNotes != null) _notesController.text = widget.initialNotes!;
    _notesController.addListener(_onNotesChanged);
    _init(); 
  }

  void _onNotesChanged() {
    _saveDraftCart();
  }

  @override
  void dispose() {
    _notesController.removeListener(_onNotesChanged);
    _notesController.dispose();
    _searchController.dispose();
    super.dispose();
  }

  _init() async { 
    final prefs = await SharedPreferences.getInstance(); 
    _baseUrl = prefs.getString('base_url') ?? "http://192.168.194.66"; 
    String dlStr = prefs.getString('deadline') ?? "";
    if (dlStr.isNotEmpty) _deadline = DateTime.tryParse(dlStr);
    _isDeadlineActive = prefs.getBool('deadline_active') ?? false;

    // CARGAR CACHÉ LOCAL INMEDIATAMENTE (0ms)
    _loadCachedData(prefs);

    await _loadDraftCart();
    _syncOfflineOrders();
    
    // ACTUALIZAR EN SEGUNDO PLANO SI HAY RED
    _fetchCustomers(); 
    _fetchProducts(); 
  }

  void _loadCachedData(SharedPreferences prefs) {
    final cachedCust = prefs.getString('cached_customers');
    if (cachedCust != null && cachedCust.isNotEmpty) {
      try {
        final List decoded = json.decode(cachedCust);
        if (mounted) {
          setState(() {
            _customers.clear();
            _customers.addAll(decoded.map((e) => Customer.fromJson(e)).toList());
          });
        }
      } catch (e) {
        debugPrint("Error leyendo clientes en caché: $e");
      }
    }

    final cachedProd = prefs.getString('cached_products');
    if (cachedProd != null && cachedProd.isNotEmpty) {
      try {
        final List decoded = json.decode(cachedProd);
        if (mounted) {
          _allProducts.clear();
          _allProducts.addAll(decoded.map((e) => Product.fromJson(e)).toList());
          _filterProducts(_searchController.text);
        }
      } catch (e) {
        debugPrint("Error leyendo productos en caché: $e");
      }
    }
  }

  Future<void> _saveDraftCart() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      if (_cart.isEmpty && _selectedCustomer == null && _notesController.text.trim().isEmpty) {
        await prefs.remove('draft_order');
        return;
      }
      final draftData = {
        'customer_id': _selectedCustomer?.id,
        'customer_name': _selectedCustomer?.name,
        'customer_debt': _selectedCustomer?.totalDebt,
        'notes': _notesController.text,
        'items': _cart.map((item) => {
          'product_id': item.product.id,
          'product_name': item.product.name,
          'product_sku': item.product.sku,
          'product_price': item.product.price,
          'product_image': item.product.imagePath,
          'product_stock': item.product.stock,
          'product_available_stock': item.product.availableStock,
          'quantity': item.quantity,
          'customer_id': item.customer.id,
          'customer_name': item.customer.name,
        }).toList(),
      };
      await prefs.setString('draft_order', json.encode(draftData));
    } catch (e) {
      debugPrint("Error al guardar borrador: $e");
    }
  }

  Future<void> _loadDraftCart() async {
    if (widget.initialCart != null && widget.initialCart!.isNotEmpty) return;
    try {
      final prefs = await SharedPreferences.getInstance();
      final draftStr = prefs.getString('draft_order');
      if (draftStr != null && draftStr.isNotEmpty) {
        final draft = json.decode(draftStr);
        if (_selectedCustomer == null && draft['customer_id'] != null) {
          _selectedCustomer = Customer(
            id: draft['customer_id'],
            name: draft['customer_name'] ?? 'Cliente',
            totalDebt: (draft['customer_debt'] ?? 0.0).toDouble(),
          );
        }
        if (_notesController.text.isEmpty && draft['notes'] != null) {
          _notesController.text = draft['notes'];
        }
        if (_cart.isEmpty && draft['items'] != null) {
          final itemsList = draft['items'] as List;
          for (var item in itemsList) {
            final prod = Product(
              id: item['product_id'],
              name: item['product_name'] ?? '',
              sku: item['product_sku'] ?? '',
              price: (item['product_price'] ?? 0.0).toDouble(),
              stock: (item['product_stock'] ?? 999.0).toDouble(),
              availableStock: (item['product_available_stock'] ?? 999.0).toDouble(),
              imagePath: item['product_image'] ?? 'noimage.jpg',
            );
            final cust = Customer(
              id: item['customer_id'] ?? (_selectedCustomer?.id ?? 0),
              name: item['customer_name'] ?? (_selectedCustomer?.name ?? 'Cliente'),
            );
            _cart.add(CartItem(
              product: prod,
              customer: cust,
              quantity: (item['quantity'] ?? 1.0).toDouble(),
            ));
          }
        }
        if (mounted) setState(() {});
      }
    } catch (e) {
      debugPrint("Error al cargar borrador: $e");
    }
  }

  Future<void> _clearDraftCart() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove('draft_order');
    } catch (e) {
      debugPrint("Error al limpiar borrador: $e");
    }
  }

  Future<void> _savePendingOfflineOrder(Map<String, dynamic> body) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final List<String> pending = prefs.getStringList('pending_offline_orders') ?? [];
      body['created_at_local'] = DateTime.now().toIso8601String();
      body['customer_name'] = _selectedCustomer?.name ?? 'Cliente';
      pending.add(json.encode(body));
      await prefs.setStringList('pending_offline_orders', pending);
    } catch (e) {
      debugPrint("Error al guardar orden offline: $e");
    }
  }

  Future<void> _syncOfflineOrders() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final List<String> pending = prefs.getStringList('pending_offline_orders') ?? [];
      if (pending.isEmpty) return;

      final token = prefs.getString('token');
      final List<String> remaining = [];
      int syncedCount = 0;

      for (String orderJson in pending) {
        try {
          final Map<String, dynamic> body = json.decode(orderJson);
          final response = await http.post(
            Uri.parse('$_baseUrl/api/orders'),
            headers: {
              'Authorization': 'Bearer $token',
              'Accept': 'application/json',
              'Content-Type': 'application/json',
              'X-Device-Token': prefs.getString('device_token') ?? ''
            },
            body: json.encode(body),
          ).timeout(const Duration(seconds: 10));

          if (response.statusCode == 200) {
            syncedCount++;
          } else {
            remaining.add(orderJson);
          }
        } catch (_) {
          remaining.add(orderJson);
        }
      }

      await prefs.setStringList('pending_offline_orders', remaining);
      if (syncedCount > 0 && mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('✅ Sincronizados $syncedCount pedido(s) guardado(s) offline'),
            backgroundColor: Colors.green,
          )
        );
      }
    } catch (e) {
      debugPrint("Error en sincronizacion offline: $e");
    }
  }

  bool get _isExpired => _isDeadlineActive && _deadline != null && DateTime.now().isAfter(_deadline!);

  Future<void> _fetchCustomers() async {
    final prefs = await SharedPreferences.getInstance();
    try {
      final token = prefs.getString('token');
      final response = await http.get(Uri.parse('$_baseUrl/api/customers'), headers: {
        'Authorization': 'Bearer $token', 
        'Accept': 'application/json',
        'X-Device-Token': prefs.getString('device_token') ?? ''
      }).timeout(const Duration(seconds: 3));

      if (response.statusCode == 200) {
        final List decoded = json.decode(response.body);
        await prefs.setString('cached_customers', response.body);
        if (mounted) {
          setState(() {
            _customers.clear();
            _customers.addAll(decoded.map((e) => Customer.fromJson(e)).toList());
          });
        }
        return;
      }
    } catch (e) {
      debugPrint("Sin conexión al cargar clientes. Cargando de caché local...");
    }

    final cachedStr = prefs.getString('cached_customers');
    if (cachedStr != null && cachedStr.isNotEmpty) {
      try {
        final List decoded = json.decode(cachedStr);
        if (mounted) {
          setState(() {
            _customers.clear();
            _customers.addAll(decoded.map((e) => Customer.fromJson(e)).toList());
          });
        }
      } catch (err) {
        debugPrint("Error leyendo clientes en caché: $err");
      }
    }
  }

  Future<void> _fetchProducts([String search = '']) async {
    setState(() { _isLoading = true; _errorMessage = null; });
    final prefs = await SharedPreferences.getInstance();
    try {
      final token = prefs.getString('token');
      String url = '$_baseUrl/api/products';
      if (_selectedCustomer != null) url += '?customer_id=${_selectedCustomer!.id}';
      final response = await http.get(Uri.parse(url), headers: {
        'Authorization': 'Bearer $token', 
        'Accept': 'application/json',
        'X-Device-Token': prefs.getString('device_token') ?? ''
      }).timeout(const Duration(seconds: 3));

      if (response.statusCode == 200) {
        final List decoded = json.decode(response.body);
        if (_selectedCustomer == null) {
          await prefs.setString('cached_products', response.body);
        }
        if (mounted) {
          _allProducts.clear();
          _allProducts.addAll(decoded.map((e) => Product.fromJson(e)).toList());
          _filterProducts(_searchController.text);
          setState(() => _isLoading = false);
        }
        return;
      }
    } catch (e) {
      debugPrint("Sin conexión al cargar productos. Cargando de caché local...");
    }

    final cachedStr = prefs.getString('cached_products');
    if (cachedStr != null && cachedStr.isNotEmpty) {
      try {
        final List decoded = json.decode(cachedStr);
        if (mounted) {
          _allProducts.clear();
          _allProducts.addAll(decoded.map((e) => Product.fromJson(e)).toList());
          _filterProducts(_searchController.text);
        }
      } catch (err) {
        debugPrint("Error leyendo productos en caché: $err");
      }
    }

    if (mounted) setState(() => _isLoading = false);
  }

  Future<void> _submitOrder() async {
    if (_isExpired) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('El periodo de pedidos ha terminado.'), backgroundColor: Colors.red));
      return;
    }
    if (_cart.isEmpty) return;
    if (_selectedCustomer == null) { ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Seleccione un cliente'))); return; }

    setState(() => _isLoading = true);
    final items = _cart.where((i) => i.customer.id == _selectedCustomer!.id).map((i) => {'product_id': i.product.id, 'quantity': i.quantity, 'price': i.product.price}).toList();

    final body = {
      'customer_id': _selectedCustomer!.id, 
      'items': items, 
      'notes': _notesController.text
    };
    if (widget.originalOrderId != null) body['original_order_id'] = widget.originalOrderId!;

    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');

      final response = await http.post(
        Uri.parse('$_baseUrl/api/orders'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Device-Token': prefs.getString('device_token') ?? ''
        },
        body: json.encode(body),
      ).timeout(const Duration(seconds: 12));

      if (response.statusCode == 200) {
        await _clearDraftCart();
        setState(() { _cart.clear(); _selectedCustomer = null; _notesController.clear(); });
        if (mounted) { ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('PRE-ORDEN GUARDADA EXITOSAMENTE'), backgroundColor: Colors.green)); Navigator.pop(context); }
      } else {
        final err = json.decode(response.body)['message'] ?? response.body;
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $err')));
      }
    } catch (e) {
      await _savePendingOfflineOrder(body);
      await _clearDraftCart();
      setState(() { _cart.clear(); _selectedCustomer = null; _notesController.clear(); });
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('📱 SIN CONEXIÓN: Pedido guardado en el teléfono. Se enviará automáticamente al reconectarse.'), 
            backgroundColor: Colors.orange,
            duration: Duration(seconds: 5),
          )
        );
        Navigator.pop(context);
      }
    } finally {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FA),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        iconTheme: const IconThemeData(color: Color(0xFF00B4D8)),
        title: Text(
          widget.originalOrderId != null ? 'Edición' : 'Catálogo', 
          style: const TextStyle(color: Colors.black, fontWeight: FontWeight.w900, fontSize: 20)
        ),
        actions: [
          Stack(
            alignment: Alignment.center,
            children: [
              IconButton(icon: const Icon(Icons.shopping_bag_outlined, size: 28), onPressed: () => _showCart()),
              if (_cart.isNotEmpty) Positioned(
                right: 8, top: 12, 
                child: Container(
                  padding: const EdgeInsets.all(4),
                  decoration: const BoxDecoration(color: Colors.red, shape: BoxShape.circle),
                  constraints: const BoxConstraints(minWidth: 16, minHeight: 16),
                  child: Text(_cart.length.toString(), style: const TextStyle(color: Colors.white, fontSize: 9, fontWeight: FontWeight.bold), textAlign: TextAlign.center),
                )
              )
            ]
          ),
          const SizedBox(width: 10)
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => _fetchProducts(_searchController.text),
        color: const Color(0xFF00B4D8),
        child: Column(children: [
          if (_isExpired) Container(
            padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 15), 
            width: double.infinity,
            color: Colors.red.shade400, 
            child: const Row(mainAxisAlignment: MainAxisAlignment.center, children: [Icon(Icons.lock_outline, color: Colors.white, size: 14), SizedBox(width: 10), Text("RECEPCIÓN DE PEDIDOS CERRADA", style: TextStyle(color: Colors.white, fontWeight: FontWeight.w900, fontSize: 11, letterSpacing: 0.5))])
          ),
          _customerSelectionHeader(),
          _searchBar(),
          if (_errorMessage != null) _errorDisplay(),
          if (_cart.isNotEmpty) _cartBanner(),
          Expanded(child: _productList()),
        ]),
      ),
    );
  }

  Widget _customerSelectionHeader() {
    return Container(
      padding: const EdgeInsets.all(15), 
      child: InkWell(
        onTap: _isExpired ? null : _showCustomerPicker,
        borderRadius: BorderRadius.circular(20),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 15),
          decoration: BoxDecoration(
            color: Colors.white, 
            borderRadius: BorderRadius.circular(20),
            boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.04), blurRadius: 10, offset: const Offset(0, 4))]
          ),
          child: Row(children: [
            Container(padding: const EdgeInsets.all(8), decoration: BoxDecoration(color: const Color(0xFF00B4D8).withOpacity(0.1), borderRadius: BorderRadius.circular(10)), child: const Icon(Icons.person_outline_rounded, color: Color(0xFF00B4D8))),
            const SizedBox(width: 15), 
            Expanded(child: Text(_selectedCustomer?.name ?? "SELECCIONAR CLIENTE", style: TextStyle(fontWeight: FontWeight.w800, color: _selectedCustomer == null ? Colors.grey.shade400 : Colors.black, fontSize: 14))), 
            const Icon(Icons.keyboard_arrow_down_rounded, color: Colors.grey)
          ]),
        ),
      ),
    );
  }

  Widget _searchBar() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 15), 
      child: TextField(
        controller: _searchController, 
        decoration: InputDecoration(
          hintText: 'Buscar producto por nombre o SKU...', 
          hintStyle: TextStyle(color: Colors.grey.shade400),
          prefixIcon: const Icon(Icons.search_rounded, color: Color(0xFF00B4D8)), 
          suffixIcon: _searchController.text.isNotEmpty 
            ? IconButton(
                icon: const Icon(Icons.clear_rounded, color: Colors.grey),
                onPressed: () {
                  _searchController.clear();
                  _filterProducts('');
                },
              )
            : null,
          filled: true,
          fillColor: Colors.white,
          contentPadding: const EdgeInsets.symmetric(vertical: 15),
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(20), borderSide: BorderSide.none),
          enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(20), borderSide: BorderSide.none),
        ), 
        onChanged: (v) => _filterProducts(v),
        onSubmitted: (v) => _filterProducts(v),
      )
    );
  }

  Widget _errorDisplay() {
    return Padding(padding: const EdgeInsets.all(15), child: Text(_errorMessage!, style: const TextStyle(color: Colors.red, fontWeight: FontWeight.bold)));
  }

  Widget _cartBanner() {
    final bool isEditing = widget.originalOrderId != null;
    return Container(
      margin: const EdgeInsets.only(top: 15, left: 15, right: 15),
      padding: const EdgeInsets.all(15), 
      decoration: BoxDecoration(
        color: isEditing ? Colors.orange.shade50 : const Color(0xFFCAF0F8), 
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: isEditing ? Colors.orange.shade100 : const Color(0xFF90E0EF))
      ),
      child: Row(children: [
        Icon(isEditing ? Icons.edit_note_rounded : Icons.shopping_basket_outlined, color: isEditing ? Colors.orange.shade800 : const Color(0xFF0077B6)), 
        const SizedBox(width: 10), 
        Expanded(child: Text(isEditing ? 'Editando Pre-Orden:' : 'Borrador actual:', style: TextStyle(fontWeight: FontWeight.w900, color: isEditing ? Colors.orange.shade900 : const Color(0xFF0077B6), fontSize: 13))), 
        Text('\$${_cartTotal.toStringAsFixed(2)}', style: TextStyle(color: isEditing ? Colors.orange.shade900 : const Color(0xFF03045E), fontWeight: FontWeight.w900, fontSize: 18))
      ])
    );
  }

  Widget _productList() {
    if (_isLoading) return const Center(child: CircularProgressIndicator(color: Color(0xFF00B4D8)));
    if (_products.isEmpty) return Center(child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [Icon(Icons.search_off_rounded, size: 60, color: Colors.grey.shade300), const SizedBox(height: 10), const Text("No hay resultados", style: TextStyle(color: Colors.grey))]));
    return ListView.builder(
      itemCount: _products.length,
      padding: const EdgeInsets.all(15),
      itemBuilder: (context, i) {
        final p = _products[i];
        final bool inStock = p.stock > 0;
        return Container(
          margin: const EdgeInsets.only(bottom: 15),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(25),
            boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.03), blurRadius: 15, offset: const Offset(0, 8))]
          ),
          child: Padding(
            padding: const EdgeInsets.all(15),
            child: Row(children: [
              Container(
                decoration: BoxDecoration(borderRadius: BorderRadius.circular(20), boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 5)]),
                child: ClipRRect(borderRadius: BorderRadius.circular(20), child: CachedNetworkImage(imageUrl: '$_baseUrl/${p.imagePath}', width: 90, height: 90, fit: BoxFit.cover, errorWidget: (c, u, e) => Container(color: Colors.grey.shade100, child: const Icon(Icons.image, size: 50, color: Colors.grey)))),
              ),
              const SizedBox(width: 15),
              Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text(p.name, style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w900, color: Color(0xFF1B263B)), maxLines: 2, overflow: TextOverflow.ellipsis),
                const SizedBox(height: 4),
                Text('SKU: ${p.sku}', style: TextStyle(color: Colors.grey.shade500, fontSize: 11, fontWeight: FontWeight.bold)),
                const SizedBox(height: 8),
                    Row(children: [
                       Container(
                         padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                         decoration: BoxDecoration(color: (p.availableStock > 0 ? const Color(0xFFE0FBFC) : Colors.red.shade50), borderRadius: BorderRadius.circular(8)),
                         child: Row(
                           children: [
                             Icon(p.availableStock > 0 ? Icons.check_circle_outline : Icons.error_outline_rounded, size: 10, color: p.availableStock > 0 ? const Color(0xFF00B4D8) : Colors.red),
                             const SizedBox(width: 4),
                             Text(p.availableStock > 0 ? '${p.availableStock.toStringAsFixed(0)} Dispon.' : 'AGOTADO', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w900, color: p.availableStock > 0 ? const Color(0xFF0077B6) : Colors.red.shade700)),
                           ],
                         ),
                       ),
                       if (p.reservedStock > 0) ... [
                         const SizedBox(width: 8),
                         Container(
                           padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 4),
                           decoration: BoxDecoration(color: Colors.orange.shade50, borderRadius: BorderRadius.circular(8)),
                           child: Text('Resv: ${p.reservedStock}', style: TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: Colors.orange.shade900)),
                         )
                       ]
                    ]),
              ])),
              Column(crossAxisAlignment: CrossAxisAlignment.end, children: [
                Text('\$${p.price.toStringAsFixed(2)}', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: Color(0xFF00B4D8))),
                const SizedBox(height: 10),
                if (!_isExpired) InkWell(
                  onTap: () {
                    if (_selectedCustomer == null) { 
                      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Seleccione un cliente primero'))); 
                      _showCustomerPicker(); 
                      return; 
                    }
                    
                    // STOCK VALIDATION
                    double currentInCart = _cart.where((i) => i.product.id == p.id && i.customer.id == _selectedCustomer!.id)
                                          .fold(0, (sum, i) => sum + i.quantity);
                    
                    if (p.checkReservation && (currentInCart + 1.0) > p.availableStock) {
                       ScaffoldMessenger.of(context).showSnackBar(
                         SnackBar(
                           backgroundColor: Colors.orange.shade800,
                           content: Row(children: [
                             const Icon(Icons.inventory_2_rounded, color: Colors.white, size: 20),
                             const SizedBox(width: 10),
                             Expanded(child: Text('¡STOCK LIMITADO! Solo quedan ${p.availableStock.toStringAsFixed(0)} disp. por reservas.', style: const TextStyle(fontWeight: FontWeight.bold))),
                           ]),
                         )
                       );
                       return;
                    }

                    setState(() {
                      int idx = _cart.indexWhere((i) => i.product.id == p.id && i.customer.id == _selectedCustomer!.id);
                      if (idx != -1) { 
                        _cart[idx].quantity += 1.0; 
                      } else { 
                        _cart.add(CartItem(product: p, customer: _selectedCustomer!)); 
                      }
                      _saveDraftCart();
                    });
                  },
                  child: Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: (p.availableStock <= 0 && p.checkReservation) ? Colors.grey.shade300 : const Color(0xFF00B4D8), 
                      borderRadius: BorderRadius.circular(15), 
                      boxShadow: (p.availableStock <= 0 && p.checkReservation) ? [] : [BoxShadow(color: const Color(0xFF00B4D8).withOpacity(0.3), blurRadius: 8, offset: const Offset(0, 4))]
                    ),
                    child: Icon((p.availableStock <= 0 && p.checkReservation) ? Icons.block_rounded : Icons.add_rounded, color: Colors.white, size: 22),
                  ),
                )
              ])
            ]),
          ),
        );
      },
    );
  }

  void _showCustomerPicker() {
    String localSearch = "";
    showModalBottomSheet(context: context, isScrollControlled: true, shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(30))), builder: (context) => StatefulBuilder(builder: (context, setModalState) {
      final filtered = _customers.where((c) => c.name.toLowerCase().contains(localSearch.toLowerCase())).toList();
      return Container(height: MediaQuery.of(context).size.height * 0.8, padding: const EdgeInsets.all(25), child: Column(children: [
        Container(width: 40, height: 4, decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(10)), margin: const EdgeInsets.only(bottom: 20)),
        const Text("Buscador de Clientes", style: TextStyle(fontSize: 20, fontWeight: FontWeight.w900)),
        const SizedBox(height: 20),
        TextField(autofocus: true, decoration: InputDecoration(hintText: "Nombre del cliente...", prefixIcon: const Icon(Icons.search_rounded, color: Color(0xFF00B4D8)), filled: true, fillColor: const Color(0xFFF8F9FA), border: OutlineInputBorder(borderRadius: BorderRadius.circular(20), borderSide: BorderSide.none)), onChanged: (v) => setModalState(() => localSearch = v)),
        const SizedBox(height: 20),
        Expanded(child: ListView.separated(separatorBuilder: (c, i) => const Divider(height: 1), itemCount: filtered.length, itemBuilder: (context, i) => ListTile(contentPadding: const EdgeInsets.symmetric(vertical: 5), leading: Container(padding: const EdgeInsets.all(10), decoration: BoxDecoration(color: const Color(0xFF00B4D8).withOpacity(0.1), shape: BoxShape.circle), child: const Icon(Icons.person_rounded, color: Color(0xFF00B4D8), size: 20)), title: Text(filtered[i].name, style: const TextStyle(fontWeight: FontWeight.bold)), onTap: () { setState(() => _selectedCustomer = filtered[i]); _saveDraftCart(); _fetchProducts(); Navigator.pop(context); })))
      ]));
    }));
  }

  void _showCart() {
    showModalBottomSheet(context: context, isScrollControlled: true, shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(35))), builder: (context) => StatefulBuilder(builder: (context, setModalState) {
      return Padding(
        padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
        child: Container(
          height: MediaQuery.of(context).size.height * 0.85, 
          padding: const EdgeInsets.all(25),
          child: Column(children: [
            Container(width: 40, height: 5, decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(10)), margin: const EdgeInsets.only(bottom: 15)),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text('Pre-Orden Actual', style: TextStyle(fontSize: 22, fontWeight: FontWeight.w900)),
                if (_cart.isNotEmpty && !_isExpired) IconButton(icon: const Icon(Icons.delete_sweep_rounded, color: Colors.red, size: 28), onPressed: () { setState(() => _cart.clear()); _clearDraftCart(); setModalState(() {}); Navigator.pop(context); }),
              ],
            ),
            const SizedBox(height: 10),
            Expanded(
              child: ListView.builder(itemCount: _cart.length, itemBuilder: (c, i) => Container(
                margin: const EdgeInsets.only(bottom: 10),
                decoration: BoxDecoration(color: const Color(0xFFF8F9FA), borderRadius: BorderRadius.circular(20)),
                child: ListTile(
                  contentPadding: const EdgeInsets.symmetric(horizontal: 15, vertical: 5),
                  leading: Container(width: 45, height: 45, decoration: const BoxDecoration(color: Color(0xFF00B4D8), shape: BoxShape.circle), child: Center(child: Text(_cart[i].quantity.toStringAsFixed(0), style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w900)))), 
                  title: Text(_cart[i].product.name, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)), 
                  subtitle: Text(_cart[i].customer.name, style: TextStyle(fontSize: 11, color: Colors.grey.shade600)), 
                  trailing: Row(mainAxisSize: MainAxisSize.min, children: [
                    Text('\$${_cart[i].total.toStringAsFixed(2)}', style: const TextStyle(fontWeight: FontWeight.w900, color: Color(0xFF03045E))),
                    const SizedBox(width: 10),
                    if (!_isExpired) ...[
                      InkWell(onTap: () { setState(() { if (_cart[i].quantity > 1.0) { _cart[i].quantity -= 1.0; } else { _cart.removeAt(i); } _saveDraftCart(); }); setModalState(() {}); if (_cart.isEmpty) Navigator.pop(context); }, child: const Icon(Icons.remove_circle_outline_rounded, color: Colors.orange)),
                      const SizedBox(width: 10),
                      InkWell(
                        onTap: () { 
                          // Cart Inventory Validation
                          if (_cart[i].product.checkReservation && (_cart[i].quantity + 1.0) > _cart[i].product.availableStock) {
                             ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('No hay más stock disponible (Reservado)'), backgroundColor: Colors.orange));
                             return;
                          }
                          setState(() { _cart[i].quantity += 1.0; _saveDraftCart(); }); 
                          setModalState(() {}); 
                        }, 
                        child: const Icon(Icons.add_circle_outline_rounded, color: Color(0xFF00B4D8))
                      ),
                    ]
                  ]),
                ),
              )),
            ),
            const Divider(),
            const SizedBox(height: 10),
            TextField(controller: _notesController, decoration: InputDecoration(labelText: 'Notas / Observaciones', filled: true, fillColor: const Color(0xFFF8F9FA), border: OutlineInputBorder(borderRadius: BorderRadius.circular(20), borderSide: BorderSide.none), prefixIcon: const Icon(Icons.edit_note_rounded, color: Color(0xFF00B4D8))), maxLines: 2, enabled: !_isExpired),
            const SizedBox(height: 20),
            Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [const Text('TOTAL:', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w900)), Text('\$${_cartTotal.toStringAsFixed(2)}', style: const TextStyle(fontSize: 26, fontWeight: FontWeight.w900, color: Color(0xFF00B4D8)))]),
            const SizedBox(height: 20),
            if (!_isExpired) SizedBox(width: double.infinity, height: 60, child: ElevatedButton(style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF00B4D8), foregroundColor: Colors.white, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(25)), elevation: 5, shadowColor: const Color(0xFF00B4D8).withOpacity(0.4)), onPressed: _isLoading || _cart.isEmpty ? null : _submitOrder, child: const Text('GUARDAR EN OFICINA', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w900, letterSpacing: 1)))),
            const SizedBox(height: 15),
          ]),
        ),
      );
    }));
  }
}

// --- ORDERS HISTORY SCREEN ---
class OrdersScreen extends StatefulWidget {
  const OrdersScreen({super.key});
  @override
  State<OrdersScreen> createState() => _OrdersScreenState();
}

class _OrdersScreenState extends State<OrdersScreen> {
  List<dynamic> _orders = [];
  bool _isLoading = true;
  String _baseUrl = "";
  DateTime? _deadline;
  bool _isDeadlineActive = false;

  @override
  void initState() { super.initState(); _fetchOrders(); }

  Future<void> _fetchOrders() async {
    final prefs = await SharedPreferences.getInstance();
    _baseUrl = prefs.getString('base_url') ?? "http://192.168.194.66";
    String dlStr = prefs.getString('deadline') ?? "";
    if (dlStr.isNotEmpty) _deadline = DateTime.tryParse(dlStr);
    _isDeadlineActive = prefs.getBool('deadline_active') ?? false;

    await _syncPendingOfflineOrders();

    final token = prefs.getString('token');
    try {
      final response = await http.get(Uri.parse('$_baseUrl/api/orders'), headers: {
        'Authorization': 'Bearer $token', 
        'Accept': 'application/json',
        'X-Device-Token': prefs.getString('device_token') ?? ''
      }).timeout(const Duration(seconds: 15));
      if (response.statusCode == 200) {
        List all = json.decode(response.body);
        setState(() => _orders = all.where((o) => o['status'] != 'processed').toList());
      }
    } catch (e) { debugPrint("Err Historial: $e"); }
    finally { setState(() => _isLoading = false); }
  }

  Future<void> _syncPendingOfflineOrders() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final List<String> pending = prefs.getStringList('pending_offline_orders') ?? [];
      if (pending.isEmpty) return;

      final token = prefs.getString('token');
      final List<String> remaining = [];
      int synced = 0;

      for (String orderJson in pending) {
        try {
          final Map<String, dynamic> body = json.decode(orderJson);
          final response = await http.post(
            Uri.parse('$_baseUrl/api/orders'),
            headers: {
              'Authorization': 'Bearer $token',
              'Accept': 'application/json',
              'Content-Type': 'application/json',
              'X-Device-Token': prefs.getString('device_token') ?? ''
            },
            body: json.encode(body),
          ).timeout(const Duration(seconds: 10));

          if (response.statusCode == 200) {
            synced++;
          } else {
            remaining.add(orderJson);
          }
        } catch (_) {
          remaining.add(orderJson);
        }
      }

      await prefs.setStringList('pending_offline_orders', remaining);
      if (synced > 0 && mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('✅ Sincronizados $synced pedido(s) pendientes offline'), backgroundColor: Colors.green)
        );
      }
    } catch (e) {
      debugPrint("Error sincronizando en OrdersScreen: $e");
    }
  }

  bool get _isExpired => _isDeadlineActive && _deadline != null && DateTime.now().isAfter(_deadline!);

  Future<void> _sendToOffice(int id) async {
    if (_isExpired) { ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Periodo de envío cerrado.'))); return; }
    setState(() => _isLoading = true);
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token');
    try {
      final response = await http.post(Uri.parse('$_baseUrl/api/orders/$id/send'), headers: {
        'Authorization': 'Bearer $token', 
        'Accept': 'application/json',
        'X-Device-Token': prefs.getString('device_token') ?? ''
      });
      if (response.statusCode == 200) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('PEDIDO ENVIADO A OFICINA'), backgroundColor: Colors.green));
        _fetchOrders();
      } else {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: ${json.decode(response.body)['message']}')));
      }
    } catch (e) { debugPrint("Err Send: $e"); }
    finally { setState(() => _isLoading = false); }
  }

  Future<void> _deleteOrder(int id) async {
    final bool confirm = await showDialog(context: context, builder: (c) => AlertDialog(
      title: const Text('Eliminar Pre-Orden'),
      content: const Text('¿Está seguro de eliminar este borrador permanentemente?'),
      actions: [
        TextButton(onPressed: () => Navigator.pop(c, false), child: const Text('CANCELAR')),
        ElevatedButton(onPressed: () => Navigator.pop(c, true), style: ElevatedButton.styleFrom(backgroundColor: Colors.red, foregroundColor: Colors.white), child: const Text('ELIMINAR')),
      ],
    )) ?? false;

    if (!confirm) return;

    setState(() => _isLoading = true);
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token');
    try {
      final response = await http.delete(Uri.parse('$_baseUrl/api/orders/$id'), headers: {
        'Authorization': 'Bearer $token', 
        'Accept': 'application/json',
        'X-Device-Token': prefs.getString('device_token') ?? ''
      });
      if (response.statusCode == 200) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('BORRADOR ELIMINADO'), backgroundColor: Colors.red));
        _fetchOrders();
      } else {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: ${json.decode(response.body)['message']}')));
      }
    } catch (e) { debugPrint("Err Delete: $e"); }
    finally { setState(() => _isLoading = false); }
  }

  void _showOrderLogs(int id) async {
    setState(() => _isLoading = true);
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token');
    try {
      final response = await http.get(Uri.parse('$_baseUrl/api/orders/$id/logs'), headers: {
        'Authorization': 'Bearer $token', 
        'Accept': 'application/json',
        'X-Device-Token': prefs.getString('device_token') ?? ''
      });
      if (response.statusCode == 200) {
        final List logs = json.decode(response.body);
        if (mounted) {
          showModalBottomSheet(context: context, builder: (c) => Container(
            padding: const EdgeInsets.all(20),
            child: Column(children: [
              const Text("Historial de la Orden", style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
              const Divider(),
              Expanded(child: ListView.builder(itemCount: logs.length, itemBuilder: (c, i) {
                final l = logs[i];
                String action = l['action'];
                if (action == 'sent_to_office') action = 'ENVIADA A OFICINA';
                else if (action == 'reverted_to_draft') action = 'DEVUELTA A BORRADOR';
                else if (action == 'created') action = 'APERTURA DE ORDEN';
                else if (action == 'edited') action = 'EDICIÓN';
                else if (action == 'status_changed') action = 'CAMBIO DE ESTADO';
                else action = action.toUpperCase().replaceAll('_', ' ');

                IconData iconAction = Icons.access_time;
                if (action.contains('ESTADO')) iconAction = Icons.settings_backup_restore;
                if (action.contains('OFICINA')) iconAction = Icons.send;
                if (action.contains('BORRADOR')) iconAction = Icons.undo;

                return ListTile(
                  leading: Icon(iconAction, color: const Color(0xFF1A237E)),
                  title: Text(action, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF1A237E))),
                  subtitle: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('${l['user']['name'] ?? 'Sistema'} • ${l['created_at'].substring(0, 16).replaceAll("T", " ")}', style: const TextStyle(fontSize: 11, color: Colors.grey)),
                      if (l['description'] != null) ...[
                        const SizedBox(height: 4),
                        Text(l['description'], style: const TextStyle(fontSize: 12, color: Colors.black87, fontWeight: FontWeight.w500)),
                      ],
                    ],
                  ),
                  isThreeLine: true,
                );
              }))
            ]),
          ));
        }
      }
    } catch (e) { debugPrint("Logs Err: $e"); }
    finally { setState(() => _isLoading = false); }
  }

  void _editOrder(dynamic o) {
    if (_isExpired) { ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Periodo de edición cerrado.'), backgroundColor: Colors.red)); return; }
    showDialog(context: context, builder: (context) => AlertDialog(
      title: const Text('Editar Pre-Orden'),
      content: const Text('Se cargará este borrador al catálogo para editarlo.'),
      actions: [
        TextButton(onPressed: () => Navigator.pop(context), child: const Text('CANCELAR')),
        ElevatedButton(onPressed: () async {
          Navigator.pop(context);
          setState(() => _isLoading = true);
          try {
            final customerData = o['customer'];
            final customer = Customer(id: int.parse(customerData['id'].toString()), name: customerData['name']);
            final List<CartItem> newCart = (o['details'] as List).map((d) {
               final p = Product.fromJson(d['product']);
               return CartItem(product: p, customer: customer, quantity: double.parse(d['quantity'].toString()));
            }).toList();
            if (mounted) {
              setState(() => _isLoading = false);
              Navigator.pushReplacement(context, MaterialPageRoute(builder: (context) => CatalogScreen(
                initialCart: newCart, initialCustomer: customer, initialNotes: o['notes'], originalOrderId: int.parse(o['id'].toString()),
              )));
            }
          } catch (e) {
            ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e')));
            if (mounted) setState(() => _isLoading = false);
          }
        }, child: const Text('EDITAR')),
      ],
    ));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FA),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        iconTheme: const IconThemeData(color: Color(0xFF00B4D8)),
        title: const Text('Mis Pedidos', style: TextStyle(color: Colors.black, fontWeight: FontWeight.w900, fontSize: 20)),
      ),
      body: _isLoading ? const Center(child: CircularProgressIndicator(color: Color(0xFF00B4D8))) : RefreshIndicator(
        onRefresh: _fetchOrders,
        color: const Color(0xFF00B4D8),
        child: _orders.isEmpty 
          ? Center(child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [Icon(Icons.inventory_2_outlined, size: 70, color: Colors.grey.shade300), const SizedBox(height: 10), const Text("No hay pedidos activos", style: TextStyle(color: Colors.grey))]))
          : ListView.builder(
            itemCount: _orders.length,
            padding: const EdgeInsets.all(15),
            itemBuilder: (context, i) {
              final o = _orders[i];
              final bool isDraft = o['status'] == 'draft';
              final bool isPending = o['status'] == 'pending';
              
              return Container(
                margin: const EdgeInsets.only(bottom: 15),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(25),
                  boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.03), blurRadius: 15, offset: const Offset(0, 8))]
                ),
                child: Theme(
                  data: Theme.of(context).copyWith(dividerColor: Colors.transparent),
                  child: ExpansionTile(
                    tilePadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
                    leading: Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(color: (isDraft ? const Color(0xFF00B4D8) : Colors.orange).withOpacity(0.1), shape: BoxShape.circle),
                      child: Icon(isDraft ? Icons.edit_document : Icons.send_and_archive, color: isDraft ? const Color(0xFF00B4D8) : Colors.orange, size: 22),
                    ),
                    title: Text('Folio: ${o['order_number']}', style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 15, color: Color(0xFF1B263B))),
                    subtitle: Text(o['customer']['name'], style: TextStyle(fontSize: 12, color: Colors.grey.shade600, fontWeight: FontWeight.bold)),
                    trailing: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                         Text('\$${double.parse(o['total'].toString()).toStringAsFixed(2)}', style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 15, color: Color(0xFF03045E))),
                         const SizedBox(height: 4),
                         Container(
                           padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                           decoration: BoxDecoration(color: isDraft ? const Color(0xFF00B4D8).withOpacity(0.15) : Colors.orange.shade50, borderRadius: BorderRadius.circular(8)),
                           child: Text(isDraft ? 'BORRADOR' : 'EN OFICINA', style: TextStyle(color: isDraft ? const Color(0xFF00B4D8) : Colors.orange.shade800, fontSize: 8, fontWeight: FontWeight.w900, letterSpacing: 0.5)),
                         ),
                      ],
                    ),
                    children: [
                      Padding(
                        padding: const EdgeInsets.only(left: 20, right: 20, bottom: 20, top: 0),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Divider(height: 1),
                            const SizedBox(height: 15),
                            if (o['notes'] != null && o['notes'].toString().isNotEmpty) Padding(
                              padding: const EdgeInsets.only(bottom: 10),
                              child: Row(children: [const Icon(Icons.notes, size: 14, color: Colors.grey), const SizedBox(width: 8), Expanded(child: Text(o['notes'], style: TextStyle(fontStyle: FontStyle.italic, color: Colors.grey.shade700, fontSize: 12)))]),
                            ),
                            Text('DETALLE DE PRODUCTOS', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w900, color: Colors.grey.shade400, letterSpacing: 1)),
                            const SizedBox(height: 8),
                            ... (o['details'] as List).map((d) => Padding(
                              padding: const EdgeInsets.symmetric(vertical: 4),
                              child: Row(children: [
                                Text('• ', style: TextStyle(color: const Color(0xFF00B4D8), fontWeight: FontWeight.bold)),
                                Expanded(child: Text(d['product']['name'], style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600))),
                                Text('x${d['quantity']}', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w900)),
                              ]),
                            )).toList(),
                            const SizedBox(height: 15),
                            const Divider(height: 1),
                            const SizedBox(height: 15),
                            Row(
                              children: [
                                _orderAction(Icons.history_rounded, 'LOGS', Colors.grey.shade600, () => _showOrderLogs(o['id'])),
                                if (isDraft) ...[
                                  const SizedBox(width: 10),
                                  _orderAction(Icons.edit_rounded, 'EDITAR', const Color(0xFF00B4D8), () => _editOrder(o)),
                                  const SizedBox(width: 10),
                                  _orderAction(Icons.send_rounded, 'ENVIAR', Colors.green.shade600, () => _sendToOffice(o['id'])),
                                  const Spacer(),
                                  IconButton(visualDensity: VisualDensity.compact, onPressed: () => _deleteOrder(o['id']), icon: const Icon(Icons.delete_outline_rounded, color: Colors.red, size: 20)),
                                ],
                                if (isPending) ...[
                                  const Spacer(),
                                  Row(children: [const Icon(Icons.lock_outline_rounded, size: 14, color: Colors.grey), const SizedBox(width: 5), Text("PEDIDO BLOQUEADO", style: TextStyle(color: Colors.grey.shade500, fontSize: 10, fontWeight: FontWeight.w900))]),
                                ]
                              ],
                            ),
                          ],
                        ),
                      )
                    ],
                  ),
                ),
              );
            },
          ),
      ),
    );
  }

  Widget _orderAction(IconData icon, String label, Color color, VoidCallback onTap) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        decoration: BoxDecoration(color: color.withOpacity(0.08), borderRadius: BorderRadius.circular(12)),
        child: Row(children: [
          Icon(icon, size: 16, color: color),
          const SizedBox(width: 6),
          Text(label, style: TextStyle(color: color, fontSize: 10, fontWeight: FontWeight.w900, letterSpacing: 0.5))
        ]),
      ),
    );
  }
}

// --- PAYMENT SCREENS ---

class PaymentCustomersScreen extends StatefulWidget {
  const PaymentCustomersScreen({super.key});
  @override
  State<PaymentCustomersScreen> createState() => _PaymentCustomersScreenState();
}

class _PaymentCustomersScreenState extends State<PaymentCustomersScreen> {
  final List<Customer> _allCustomers = [];
  List<Customer> _filteredCustomers = [];
  bool _isLoading = false;
  String _baseUrl = "";
  String _currentFilter = 'all'; // 'all', 'debt', 'overdue'
  final _searchController = TextEditingController();

  @override
  void initState() { super.initState(); _init(); }
  _init() async { 
    final prefs = await SharedPreferences.getInstance(); 
    _baseUrl = prefs.getString('base_url') ?? "http://192.168.194.66"; 
    _fetchCustomers(); 
  }

  Future<void> _fetchCustomers([String search = '', String? filter]) async {
    setState(() => _isLoading = true);
    final currentFilter = filter ?? _currentFilter;
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');
      // Pass the filter to the API
      final response = await http.get(
        Uri.parse('$_baseUrl/api/customers?search=$search&filter=$currentFilter'), 
        headers: {
          'Authorization': 'Bearer $token', 
          'Accept': 'application/json',
          'X-Device-Token': prefs.getString('device_token') ?? ''
        }
      ).timeout(const Duration(seconds: 15));
      
      if (response.statusCode == 200) setState(() { 
        _allCustomers.clear(); 
        _allCustomers.addAll((json.decode(response.body) as List).map((e) => Customer.fromJson(e)).toList()); 
        _filteredCustomers = List.from(_allCustomers);
      });
    } catch (e) { debugPrint("Err: $e"); }
    finally { setState(() => _isLoading = false); }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FA),
      appBar: AppBar(
        title: const Text('Registrar Abono', style: TextStyle(fontWeight: FontWeight.w900)),
        backgroundColor: Colors.white,
        elevation: 0,
        iconTheme: const IconThemeData(color: Color(0xFF00B4D8)),
      ),
      body: Column(
        children: [
          Container(
            padding: const EdgeInsets.fromLTRB(20, 10, 20, 20),
            decoration: const BoxDecoration(color: Colors.white, borderRadius: BorderRadius.vertical(bottom: Radius.circular(30))),
            child: Column(
              children: [
                TextField(
                  controller: _searchController,
                  decoration: InputDecoration(
                    hintText: 'Buscar cliente...', 
                    prefixIcon: const Icon(Icons.search, color: Color(0xFF00B4D8)), 
                    filled: true,
                    fillColor: const Color(0xFFF1F3F5),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(20), borderSide: BorderSide.none)
                  ),
                  onChanged: (v) => _fetchCustomers(v),
                ),
                const SizedBox(height: 15),
                SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  child: Row(
                    children: [
                      _filterChip('TODOS', 'all', Icons.people_rounded, Colors.blue),
                      _filterChip('CON DEUDA', 'debt', Icons.money_off_rounded, Colors.orange),
                      _filterChip('VENCIDOS', 'overdue', Icons.warning_amber_rounded, Colors.red),
                    ],
                  ),
                )
              ],
            ),
          ),
          const Padding(
            padding: EdgeInsets.only(top: 20, left: 25, right: 25, bottom: 10),
            child: Row(children: [Text("Resultados", style: TextStyle(fontWeight: FontWeight.w900, color: Color(0xFF1B263B), fontSize: 16))]),
          ),
          Expanded(
            child: _isLoading ? const Center(child: CircularProgressIndicator()) : ListView.builder(
              itemCount: _filteredCustomers.length,
              padding: const EdgeInsets.symmetric(horizontal: 15),
              itemBuilder: (c, i) {
                final cust = _filteredCustomers[i];
                Color statusColor = cust.hasOverdue ? Colors.red : (cust.totalDebt > 0 ? Colors.orange : const Color(0xFF00B4D8));
                
                return Container(
                  margin: const EdgeInsets.only(bottom: 15),
                  decoration: BoxDecoration(
                    color: Colors.white, 
                    borderRadius: BorderRadius.circular(25), 
                    border: cust.hasOverdue ? Border.all(color: Colors.red.withOpacity(0.3), width: 1.5) : null,
                    boxShadow: [BoxShadow(color: statusColor.withOpacity(0.05), blurRadius: 10, offset: const Offset(0, 4))]
                  ),
                  child: ListTile(
                    contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                    leading: Container(
                      padding: const EdgeInsets.all(12), 
                      decoration: BoxDecoration(color: statusColor.withOpacity(0.1), borderRadius: BorderRadius.circular(18)), 
                      child: Icon(cust.hasOverdue ? Icons.priority_high_rounded : Icons.person, color: statusColor)
                    ),
                    title: Text(cust.name, style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 14, color: Color(0xFF1B263B))),
                    subtitle: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const SizedBox(height: 4),
                        if (cust.totalDebt > 0) ... [
                          Row(
                            children: [
                              Text('\$${cust.totalDebt.toStringAsFixed(2)}', style: TextStyle(color: statusColor, fontWeight: FontWeight.bold, fontSize: 13)),
                              const SizedBox(width: 8),
                              Text('(${cust.pendingCount} fact.)', style: const TextStyle(fontSize: 10, color: Colors.grey)),
                            ],
                          )
                        ] else ... [
                          const Text("Sin facturas pendientes", style: TextStyle(fontSize: 11, color: Colors.green, fontWeight: FontWeight.bold)),
                        ]
                      ],
                    ),
                    trailing: const Icon(Icons.arrow_forward_ios_rounded, size: 14, color: Colors.grey),
                    onTap: () => Navigator.push(context, MaterialPageRoute(builder: (ctx) => PendingSalesScreen(customer: cust))),
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }

  Widget _filterChip(String label, String code, IconData icon, Color color) {
    bool active = _currentFilter == code;
    return GestureDetector(
      onTap: () { 
        setState(() => _currentFilter = code); 
        _fetchCustomers(_searchController.text, code); 
      },
      child: Container(
        margin: const EdgeInsets.only(right: 10),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
        decoration: BoxDecoration(
          color: active ? color : color.withOpacity(0.05),
          borderRadius: BorderRadius.circular(15),
          border: Border.all(color: active ? color : color.withOpacity(0.2))
        ),
        child: Row(
          children: [
            Icon(icon, size: 16, color: active ? Colors.white : color),
            const SizedBox(width: 8),
            Text(label, style: TextStyle(color: active ? Colors.white : color, fontWeight: FontWeight.w900, fontSize: 11)),
          ],
        ),
      ),
    );
  }
}

class PendingSalesScreen extends StatefulWidget {
  final Customer customer;
  const PendingSalesScreen({super.key, required this.customer});
  @override
  State<PendingSalesScreen> createState() => _PendingSalesScreenState();
}

class _PendingSalesScreenState extends State<PendingSalesScreen> {
  List<dynamic> _sales = [];
  bool _isLoading = false;
  String _baseUrl = "";

  @override
  void initState() { super.initState(); _fetchSales(); }

  _fetchSales() async {
    setState(() => _isLoading = true);
    final prefs = await SharedPreferences.getInstance();
    _baseUrl = prefs.getString('base_url') ?? "";
    final token = prefs.getString('token');
    try {
      final res = await http.get(Uri.parse('$_baseUrl/api/sales/pending?customer_id=${widget.customer.id}'), headers: {
        'Authorization': 'Bearer $token', 
        'Accept': 'application/json',
        'X-Device-Token': prefs.getString('device_token') ?? ''
      });
      if (res.statusCode == 200) setState(() => _sales = json.decode(res.body));
    } catch (e) { debugPrint("Err Sales: $e"); }
    finally { setState(() => _isLoading = false); }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FA),
      appBar: AppBar(
        title: Text(widget.customer.name, style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 16)),
        backgroundColor: Colors.white,
        elevation: 0,
        iconTheme: const IconThemeData(color: Color(0xFF00B4D8)),
      ),
      body: _isLoading ? const Center(child: CircularProgressIndicator()) : _sales.isEmpty ? const Center(child: Text("Sin facturas pendientes")) : ListView.builder(
        itemCount: _sales.length,
        padding: const EdgeInsets.all(15),
        itemBuilder: (c, i) {
          final s = _sales[i];
          double total = (s['total_usd'] as num).toDouble();
          double debt = (s['debt_usd'] as num).toDouble();
          double paid = (s['paid_usd'] != null) ? (s['paid_usd'] as num).toDouble() : (total - debt);
          double pending = (s['pending_usd'] != null) ? (s['pending_usd'] as num).toDouble() : 0.0;
          
          return Container(
            margin: const EdgeInsets.only(bottom: 15),
            decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(30), boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.04), blurRadius: 15, offset: const Offset(0, 8))]),
            child: Column(
              children: [
                Padding(
                  padding: const EdgeInsets.all(20),
                  child: Row(
                    children: [
                      Container(padding: const EdgeInsets.all(12), decoration: BoxDecoration(color: const Color(0xFF1B263B).withOpacity(0.05), borderRadius: BorderRadius.circular(15)), child: const Icon(Icons.receipt_long_rounded, color: Color(0xFF1B263B))),
                      const SizedBox(width: 15),
                      Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                        Text("Venta: ${s['invoice_number']}", style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 15)),
                        Text("Emi: ${s['date']} | Venc: ${s['due_date']}", style: const TextStyle(color: Colors.grey, fontSize: 10, fontWeight: FontWeight.bold)),
                        if (s['days_overdue'] != null) ... [
                           const SizedBox(height: 2),
                           Text(
                             s['days_overdue'] > 0 
                              ? "VENCIDA HACE ${s['days_overdue']} DÍAS" 
                              : (s['days_overdue'] == 0 ? "VENCE HOY" : "POR VENCER EN ${s['days_overdue'].abs()} DÍAS"),
                             style: TextStyle(
                               color: s['days_overdue'] > 0 ? Colors.red : (s['days_overdue'] == 0 ? Colors.orange : Colors.blue),
                               fontWeight: FontWeight.w900,
                               fontSize: 9,
                               letterSpacing: 0.5
                             ),
                           )
                        ],
                        if (pending > 0) ...[
                          const SizedBox(height: 5),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                            decoration: BoxDecoration(color: const Color(0xFFFFF3CD), borderRadius: BorderRadius.circular(8), border: Border.all(color: const Color(0xFFFFEEBA))),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                const Icon(Icons.access_time_rounded, size: 12, color: Color(0xFF856404)),
                                const SizedBox(width: 4),
                                Text("POR BAJAR: \$${pending.toStringAsFixed(2)}", style: const TextStyle(color: Color(0xFF856404), fontWeight: FontWeight.w900, fontSize: 9)),
                              ],
                            ),
                          )
                        ]
                      ])),
                      IconButton(
                        icon: const Icon(Icons.history_rounded, color: Color(0xFF00B4D8)),
                        onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (ctx) => PaymentHistoryScreen(saleId: s['id'], invoice: s['invoice_number']))),
                      )
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 15),
                  decoration: BoxDecoration(color: const Color(0xFFF1F3F5).withOpacity(0.5), borderRadius: const BorderRadius.vertical(bottom: Radius.circular(30))),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      _smallStat('TOTAL', '\$${total.toStringAsFixed(2)}', Colors.grey),
                      _smallStat('ABONADO', '\$${paid.toStringAsFixed(2)}', Colors.green),
                      if (pending > 0)
                        _smallStat('POR BAJAR', '\$${pending.toStringAsFixed(2)}', Colors.amber.shade800, isBold: true),
                      _smallStat('DEBE', '\$${debt.toStringAsFixed(2)}', Colors.red, isBold: true),
                      ElevatedButton(
                        onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (ctx) => UploadPaymentForm(saleId: s['id'], invoice: s['invoice_number'], debt: debt))),
                        style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF00B4D8), foregroundColor: Colors.white, shape: const CircleBorder(), padding: EdgeInsets.zero, minimumSize: const Size(40, 40)),
                        child: const Icon(Icons.add, size: 20),
                      )
                    ],
                  ),
                )
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _smallStat(String label, String val, Color color, {bool isBold = false}) {
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
       Text(label, style: const TextStyle(fontSize: 8, fontWeight: FontWeight.bold, color: Colors.grey, letterSpacing: 0.5)),
       Text(val, style: TextStyle(fontSize: 12, fontWeight: isBold ? FontWeight.w900 : FontWeight.w700, color: color)),
    ]);
  }
}

class PaymentHistoryScreen extends StatefulWidget {
  final int saleId;
  final String invoice;
  const PaymentHistoryScreen({super.key, required this.saleId, required this.invoice});
  @override
  State<PaymentHistoryScreen> createState() => _PaymentHistoryScreenState();
}

class _PaymentHistoryScreenState extends State<PaymentHistoryScreen> {
  List<dynamic> _history = [];
  bool _isLoading = false;

  @override
  void initState() { super.initState(); _fetchHistory(); }

  void _showReceipt(String? imagePath) {
    if (imagePath == null || imagePath.isEmpty) return;
    
    showDialog(
      context: context,
      builder: (context) => Dialog(
        backgroundColor: Colors.transparent,
        insetPadding: const EdgeInsets.all(10),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                IconButton(
                  icon: const Icon(Icons.close_fullscreen_rounded, color: Colors.white, size: 30),
                  onPressed: () => Navigator.pop(context),
                ),
              ],
            ),
            ClipRRect(
              borderRadius: BorderRadius.circular(15),
              child: CachedNetworkImage(
                imageUrl: '${_historyUrl.replaceAll('/api/payments/history', '')}/storage/$imagePath',
                placeholder: (context, url) => const Center(child: CircularProgressIndicator(color: Colors.white)),
                errorWidget: (context, url, error) => Container(
                  color: Colors.white,
                  padding: const EdgeInsets.all(40),
                  child: const Column(
                    children: [
                      Icon(Icons.broken_image_rounded, size: 50, color: Colors.grey),
                      SizedBox(height: 10),
                      Text("No se pudo cargar la imagen", style: TextStyle(color: Colors.grey)),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  String _historyUrl = "";

  _fetchHistory() async {
    setState(() => _isLoading = true);
    final prefs = await SharedPreferences.getInstance();
    final baseUrl = prefs.getString('base_url') ?? "";
    _historyUrl = '$baseUrl/api/payments/history';
    final token = prefs.getString('token');
    try {
      final res = await http.get(Uri.parse('$_historyUrl?sale_id=${widget.saleId}'), headers: {
        'Authorization': 'Bearer $token', 
        'Accept': 'application/json',
        'X-Device-Token': prefs.getString('device_token') ?? ''
      });
      if (res.statusCode == 200) setState(() => _history = json.decode(res.body));
    } catch (e) { debugPrint("Err Hist: $e"); }
    finally { setState(() => _isLoading = false); }
  }

  /// Returns the currency symbol for a given currency code
  String _currencySymbol(String? code) {
    switch (code?.toUpperCase()) {
      case 'VED':
      case 'VES':
      case 'VEF':
        return 'Bs.';
      case 'COP':
        return 'COP';
      case 'EUR':
        return '€';
      case 'USD':
        return '\$';
      default:
        return code ?? '\$';
    }
  }

  /// Calculates and formats the USD equivalent of a payment
  String _usdEquivalent(dynamic p) {
    final double amount = double.tryParse(p['amount'].toString()) ?? 0;
    final double rate = double.tryParse(p['exchange_rate']?.toString() ?? '1') ?? 1;
    final String currency = p['currency']?.toString().toUpperCase() ?? 'USD';

    if (currency == 'USD') {
      return '\$${amount.toStringAsFixed(2)}';
    }
    // Convert to USD: original_amount / exchange_rate
    final double usd = rate > 0 ? amount / rate : amount;
    return '\$${usd.toStringAsFixed(2)}';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Historial: ${widget.invoice}', style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 16)), backgroundColor: Colors.white, elevation: 0),
      body: _isLoading ? const Center(child: CircularProgressIndicator()) : _history.isEmpty ? const Center(child: Text("Sin abonos registrados")) : ListView.builder(
        itemCount: _history.length,
        padding: const EdgeInsets.all(15),
        itemBuilder: (c, i) {
          final p = _history[i];
          final bool isReturn = p['type'] == 'return';
          Color statusCol = p['status'] == 'approved' ? Colors.green : (p['status'] == 'rejected' ? Colors.red : Colors.orange);

          final String currency = p['currency']?.toString().toUpperCase() ?? 'USD';
          final double amount = double.tryParse(p['amount'].toString()) ?? 0;
          final double discount = double.tryParse(p['discount_applied']?.toString() ?? '0') ?? 0;
          final double totalAbono = amount + discount; // Assuming USD for discount
          
          final bool isNonUSD = currency != 'USD' && !isReturn;
          final bool hasDiscount = discount > 0;
          final bool hasImage = p['image'] != null && p['image'].toString().isNotEmpty;

          return Card(
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(25)),
            margin: const EdgeInsets.only(bottom: 15),
            elevation: 0,
            color: isReturn ? Colors.cyan.shade50 : Colors.white,
            child: Theme(
              data: Theme.of(context).copyWith(dividerColor: Colors.transparent),
              child: ExpansionTile(
                tilePadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                leading: Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: (isReturn ? Colors.blue : statusCol).withOpacity(0.1),
                    borderRadius: BorderRadius.circular(15)
                  ),
                  child: Icon(
                    isReturn ? Icons.assignment_return_rounded : Icons.payments_rounded, 
                    color: isReturn ? Colors.blue : statusCol,
                    size: 20
                  )
                ),
                title: Text(
                  isReturn 
                    ? "N/C - \$${amount.toStringAsFixed(2)}"
                    : "${p['method'].toString().toUpperCase()} - \$${totalAbono.toStringAsFixed(2)}", 
                  style: const TextStyle(fontWeight: FontWeight.w900, color: Color(0xFF1B263B), fontSize: 13)
                ),
                subtitle: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      "Ref: ${p['reference'] ?? 'N/A'}",
                      style: const TextStyle(fontSize: 10, color: Colors.grey, fontWeight: FontWeight.bold)
                    ),
                    if (hasDiscount) Padding(
                      padding: const EdgeInsets.only(top: 2),
                      child: Text(
                        "INCLUYE DESC: \$${discount.toStringAsFixed(2)}",
                        style: const TextStyle(fontSize: 9, color: Colors.green, fontWeight: FontWeight.w900)
                      ),
                    ),
                  ],
                ),
                trailing: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                     Text(
                       p['date'].toString(), 
                       style: const TextStyle(color: Colors.grey, fontSize: 9, fontWeight: FontWeight.bold)
                     ),
                     const SizedBox(height: 4),
                     Container(
                       padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                       decoration: BoxDecoration(color: statusCol.withOpacity(0.1), borderRadius: BorderRadius.circular(5)),
                       child: Text(
                         p['status'].toString().toUpperCase(), 
                         style: TextStyle(color: statusCol, fontSize: 8, fontWeight: FontWeight.w900)
                       ),
                     ),
                  ],
                ),
                children: [
                  Container(
                    padding: const EdgeInsets.only(left: 20, right: 20, bottom: 20),
                    child: Column(
                       children: [
                         const Divider(height: 1),
                         const SizedBox(height: 15),
                         if (isReturn && p['reason'] != null) ... [
                            _detailRow("Motivo N/C", p['reason'].toString()),
                            const SizedBox(height: 5),
                         ],
                         if (hasDiscount) ... [
                            _detailRow("Monto Pagado", "${_currencySymbol(currency)} ${amount.toStringAsFixed(2)}"),
                            _detailRow("Descuento Aplicado", "\$${discount.toStringAsFixed(2)}", highlight: true),
                            if (p['discount_reason'] != null) _detailRow("Tipo Descuento", p['discount_reason'].toString()),
                            const Divider(),
                            _detailRow("Total Abono a Deuda", "\$${totalAbono.toStringAsFixed(2)}", highlight: true),
                         ] else if (isNonUSD) ... [
                            _detailRow("Monto en ${currency}", "${_currencySymbol(currency)} ${amount.toStringAsFixed(2)}"),
                            if (p['exchange_rate'] != null) _detailRow("Tasa de Cambio", "x ${p['exchange_rate']}"),
                            _detailRow("Equivalente USD", _usdEquivalent(p), highlight: true),
                         ],
                         
                         if (p['bank'] != null) _detailRow("Banco / Plataforma", p['bank'].toString()),
                         if (p['issuer_name'] != null) _detailRow("Nombre Titular", p['issuer_name'].toString()),
                         
                         const SizedBox(height: 15),
                         Row(
                           children: [
                             if (hasImage) Expanded(
                               child: ElevatedButton.icon(
                                 onPressed: () => _showReceipt(p['image']),
                                 icon: const Icon(Icons.image_search_rounded, size: 18),
                                 label: const Text("VER COMPROBANTE", style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                                 style: ElevatedButton.styleFrom(
                                   backgroundColor: const Color(0xFF1B263B),
                                   foregroundColor: Colors.white,
                                   shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12))
                                 ),
                               ),
                             ),
                             if (isReturn) const Expanded(
                               child: Text(
                                 "Esta Nota de Crédito ha reducido el saldo deudor de la factura.",
                                 style: TextStyle(fontSize: 10, fontStyle: FontStyle.italic, color: Colors.blue),
                                 textAlign: TextAlign.center,
                               ),
                             ),
                           ],
                         ),
                       ],
                    ),
                  )
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _detailRow(String label, String val, {bool highlight = false}) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6.0),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(fontSize: 11, color: Colors.grey, fontWeight: FontWeight.w600)),
          const SizedBox(width: 20),
          Expanded(
            child: Text(
              val, 
              textAlign: TextAlign.right,
              maxLines: 5,
              style: TextStyle(
                fontSize: 11, 
                fontWeight: FontWeight.w900, 
                color: highlight ? const Color(0xFF00B4D8) : const Color(0xFF1B263B)
              )
            ),
          ),
        ],
      ),
    );
  }
}

class UploadPaymentForm extends StatefulWidget {
  final int saleId;
  final String invoice;
  final double debt;
  const UploadPaymentForm({super.key, required this.saleId, required this.invoice, required this.debt});
  @override
  State<UploadPaymentForm> createState() => _UploadPaymentFormState();
}

class _UploadPaymentFormState extends State<UploadPaymentForm> {
  final _amountController = TextEditingController();
  final _refController = TextEditingController();
  final _issuerController = TextEditingController();
  final _rateController = TextEditingController();
  
  String _payType = 'bank'; 
  String _selectedMethod = 'bank'; 
  dynamic _selectedBank;
  dynamic _selectedCurrency;
  DateTime _paymentDate = DateTime.now();
  
  List<dynamic> _banks = [];
  List<dynamic> _currencies = [];
  List<dynamic> _rateOptions = [];
  dynamic _selectedRateOption;
  File? _image;
  bool _isLoading = false;
  String _baseUrl = "";
  String _rateTypeLabel = "";

  bool _userHasEditedAmount = false;

  @override
  void initState() { 
    super.initState(); 
    _amountController.addListener(_onAmountOrRateChanged);
    _rateController.addListener(_onAmountOrRateChanged);
    _init(); 
  }

  @override
  void dispose() {
    _amountController.removeListener(_onAmountOrRateChanged);
    _rateController.removeListener(_onAmountOrRateChanged);
    super.dispose();
  }

  void _onAmountOrRateChanged() {
    setState(() {});
  }

  void _updateAmountBasedOnCurrency() {
    bool isUSD = _selectedCurrency == null || _selectedCurrency['code'] == 'USD';
    double rate = double.tryParse(_rateController.text) ?? 1.0;
    if (!_userHasEditedAmount) {
      if (!isUSD && rate > 0) {
        double localAmount = widget.debt * rate;
        _amountController.text = localAmount.toStringAsFixed(2);
      } else {
        _amountController.text = widget.debt.toStringAsFixed(2);
      }
    }
  }
  
  _init() async {
    final prefs = await SharedPreferences.getInstance();
    _baseUrl = prefs.getString('base_url') ?? "";
    final token = prefs.getString('token');
    try {
      final dateStr = DateFormat('yyyy-MM-dd').format(_paymentDate);
      final res = await http.get(Uri.parse('$_baseUrl/api/payments/form-data?sale_id=${widget.saleId}&date=$dateStr'), headers: {
        'Authorization': 'Bearer $token', 
        'Accept': 'application/json',
        'X-Device-Token': prefs.getString('device_token') ?? ''
      });
      if (res.statusCode == 200) {
        final data = json.decode(res.body);
        setState(() {
           _banks = data['banks'];
           _currencies = data['currencies'];
           if (_currencies.isNotEmpty) {
             _selectedCurrency = _currencies.firstWhere((c) => c['code'] == 'USD', orElse: () => _currencies.first);
             _rateController.text = _selectedCurrency['exchange_rate']?.toString() ?? '1.0';
           }
           if (data['rate_options'] != null && (data['rate_options'] as List).isNotEmpty) {
             _rateOptions = data['rate_options'];
             _selectedRateOption = _rateOptions.first;
             _rateController.text = _selectedRateOption['rate'].toString();
             _rateTypeLabel = _selectedRateOption['label']?.toString() ?? '';
           } else if (data['calculated_rate'] != null) {
             _rateController.text = data['calculated_rate'].toString();
             _rateTypeLabel = data['rate_type'] == 'BCV' ? 'Tasa BCV del día' : 'Tasa Divisa del día';
           }
           if (_banks.isNotEmpty) {
             _selectedBank = _banks.first;
             _selectedMethod = _selectedBank['name'].toString().toLowerCase().contains('zelle') ? 'zelle' : 'bank';
             final bankCurrency = _currencies.firstWhere((c) => c['code'] == _selectedBank['currency_code'], orElse: () => null);
             if (bankCurrency != null) _selectedCurrency = bankCurrency;
           }
           _userHasEditedAmount = false;
           _updateAmountBasedOnCurrency();
        });
      }
    } catch (e) { debugPrint("Err Data: $e"); }
  }

  Future<void> _pickImage() async {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(25))),
      builder: (context) {
        return SafeArea(
          child: Wrap(
            children: [
              ListTile(
                leading: const Icon(Icons.camera_alt, color: Color(0xFF00B4D8)),
                title: const Text('Tomar Foto', style: TextStyle(fontWeight: FontWeight.bold)),
                onTap: () {
                  Navigator.pop(context);
                  _processImage(ImageSource.camera);
                },
              ),
              ListTile(
                leading: const Icon(Icons.photo_library, color: Color(0xFF00B4D8)),
                title: const Text('Elegir de Galería', style: TextStyle(fontWeight: FontWeight.bold)),
                onTap: () {
                  Navigator.pop(context);
                  _processImage(ImageSource.gallery);
                },
              ),
            ],
          ),
        );
      },
    );
  }

  Future<void> _processImage(ImageSource source) async {
    final picker = ImagePicker();
    final pickedFile = await picker.pickImage(source: source, imageQuality: 70);
    if (pickedFile != null) setState(() => _image = File(pickedFile.path));
  }

  Future<void> _selectDate() async {
    final DateTime? picked = await showDatePicker(
      context: context, initialDate: _paymentDate, firstDate: DateTime(2024), lastDate: DateTime.now(),
      builder: (context, child) => Theme(data: Theme.of(context).copyWith(colorScheme: const ColorScheme.light(primary: Color(0xFF1B263B))), child: child!),
    );
    if (picked != null) {
      setState(() => _paymentDate = picked);
      _fetchRateForDate(picked);
    }
  }

  Future<void> _fetchRateForDate(DateTime date) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');
      final dateStr = DateFormat('yyyy-MM-dd').format(date);
      final res = await http.get(
        Uri.parse('$_baseUrl/api/payments/form-data?sale_id=${widget.saleId}&date=$dateStr'),
        headers: {
          'Authorization': 'Bearer $token', 
          'Accept': 'application/json',
          'X-Device-Token': prefs.getString('device_token') ?? ''
        }
      );
      if (res.statusCode == 200) {
        final data = json.decode(res.body);
        setState(() {
          if (data['rate_options'] != null && (data['rate_options'] as List).isNotEmpty) {
            _rateOptions = data['rate_options'];
            _selectedRateOption = _rateOptions.first;
            _rateController.text = _selectedRateOption['rate'].toString();
            _rateTypeLabel = _selectedRateOption['label']?.toString() ?? '';
          } else if (data['calculated_rate'] != null) {
            _rateOptions = [];
            _selectedRateOption = null;
            _rateController.text = data['calculated_rate'].toString();
            _rateTypeLabel = data['rate_type'] == 'BCV' ? 'Tasa BCV del día' : 'Tasa Divisa del día';
          }
          _updateAmountBasedOnCurrency();
        });
      }
    } catch (e) { debugPrint("Err Rate: $e"); }
  }

  Future<void> _submit() async {
    if (_amountController.text.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Ingrese el monto"), backgroundColor: Colors.red));
      return;
    }

    bool isUSD = _selectedCurrency == null || _selectedCurrency['code'] == 'USD';
    bool isZelle = _selectedMethod == 'zelle';

    if (_payType == 'bank' && _selectedBank == null) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Seleccione un banco o plataforma"), backgroundColor: Colors.red));
      return;
    }

    if (!isUSD && _rateController.text.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("La tasa de cambio es obligatoria"), backgroundColor: Colors.red));
      return;
    }

    if (_payType == 'bank' && _refController.text.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("El nro de referencia es obligatorio"), backgroundColor: Colors.red));
      return;
    }

    if ((isZelle || !isUSD) && _issuerController.text.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("El nombre del titular/emisor es obligatorio"), backgroundColor: Colors.red));
      return;
    }

    if (_image == null) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("La foto del comprobante/efectivo es obligatoria"), backgroundColor: Colors.red));
      return;
    }

    setState(() => _isLoading = true);
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');
      var request = http.MultipartRequest('POST', Uri.parse('$_baseUrl/api/payments/upload'));
      request.headers['Authorization'] = 'Bearer $token';
      request.headers['Accept'] = 'application/json';
      request.headers['X-Device-Token'] = prefs.getString('device_token') ?? '';
      
      request.fields['sale_id'] = widget.saleId.toString();
      request.fields['method'] = _payType == 'cash' ? 'cash' : _selectedMethod;
      request.fields['amount'] = _amountController.text;
      request.fields['currency'] = _selectedCurrency['code'];
      request.fields['exchange_rate'] = _rateController.text;
      request.fields['payment_date'] = DateFormat('yyyy-MM-dd').format(_paymentDate);
      request.fields['reference'] = _refController.text;
      request.fields['issuer_name'] = _issuerController.text;

      if (_payType == 'bank' && _selectedMethod == 'bank' && _selectedBank != null) {
        request.fields['bank_id'] = _selectedBank['id'].toString();
      }
      if (_image != null) request.files.add(await http.MultipartFile.fromPath('image', _image!.path));

      var response = await request.send();
      if (response.statusCode == 200) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("PAGO NOTIFICADO CORRECTAMENTE"), backgroundColor: Colors.green));
          Navigator.pop(context); // Go back to history/pending
        }
      } else {
        final respStr = await response.stream.bytesToString();
        final body = json.decode(respStr);
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(body['message'] ?? "Error al subir pago"), backgroundColor: Colors.red));
      }
    } catch (e) { debugPrint("Err Upload: $e"); }
    finally { if (mounted) setState(() => _isLoading = false); }
  }

  @override
  Widget build(BuildContext context) {
    bool isUSD = _selectedCurrency == null || _selectedCurrency['code'] == 'USD';
    String currCode = _selectedCurrency?['code'] ?? 'USD';
    bool isVED = currCode == 'VED';
    String currSymbol = _selectedCurrency?['symbol'] ?? (currCode == 'VED' ? 'Bs.' : (currCode == 'COP' ? 'COP' : '\$'));

    double amountLocal = double.tryParse(_amountController.text) ?? 0.0;
    double rate = double.tryParse(_rateController.text) ?? 1.0;
    double amountUsd = !isUSD ? (rate > 0 ? (amountLocal / rate) : 0.0) : amountLocal;

    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FA),
      appBar: AppBar(
        title: Text('Abono: ${widget.invoice}', style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 16)), 
        backgroundColor: Colors.white, 
        elevation: 0,
        iconTheme: const IconThemeData(color: Color(0xFF00B4D8)),
      ),
      body: _isLoading ? const Center(child: CircularProgressIndicator()) : SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              padding: const EdgeInsets.all(25), width: double.infinity,
              decoration: BoxDecoration(gradient: const LinearGradient(colors: [Color(0xFF1B263B), Color(0xFF415A77)], begin: Alignment.topLeft, end: Alignment.bottomRight), borderRadius: BorderRadius.circular(30)),
              child: Column(children: [
                const Text('Monto Pendiente', style: TextStyle(color: Colors.white70, fontSize: 12, fontWeight: FontWeight.bold)),
                Text('\$${widget.debt.toStringAsFixed(2)}', style: const TextStyle(color: Colors.white, fontSize: 32, fontWeight: FontWeight.w900)),
              ]),
            ),
            const SizedBox(height: 30),
            
            _sectionTitle("Método de Pago"),
            const SizedBox(height: 15),
            Row(children: [
               Expanded(child: _methodButton('Efectivo', Icons.payments_rounded, Colors.green, _payType == 'cash', () => setState(() {
                 _payType = 'cash';
                 _userHasEditedAmount = false;
                 _updateAmountBasedOnCurrency();
               }))),
               const SizedBox(width: 15),
               Expanded(child: _methodButton('Banco / Zelle', Icons.account_balance_rounded, const Color(0xFF00B4D8), _payType == 'bank', () => setState(() {
                 _payType = 'bank';
                 _userHasEditedAmount = false;
                 _updateAmountBasedOnCurrency();
               }))),
            ]),
            
            const SizedBox(height: 25),
            if (_payType == 'bank') ... [
              _sectionTitle("Banco / Plataforma"),
              const SizedBox(height: 10),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12), decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(15), boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.02), blurRadius: 5)]),
                child: DropdownButtonHideUnderline(child: DropdownButton(
                  value: _selectedBank, isExpanded: true, hint: const Text("Seleccione Banco..."),
                  items: _banks.map((b) => DropdownMenuItem(value: b, child: Text("${b['name']} (${b['currency_code'] ?? 'USD'})", style: const TextStyle(fontWeight: FontWeight.bold)))).toList(),
                  onChanged: (dynamic v) => setState(() {
                    _selectedBank = v;
                    _selectedMethod = v['name'].toString().toLowerCase().contains('zelle') ? 'zelle' : 'bank';
                    
                    final bankCurrency = _currencies.firstWhere((c) => c['code'] == v['currency_code'], orElse: () => null);
                    if (bankCurrency != null) {
                      _selectedCurrency = bankCurrency;
                      if (bankCurrency['code'] != 'USD') {
                        _rateController.text = bankCurrency['exchange_rate']?.toString() ?? '1.0';
                      }
                    }
                    _userHasEditedAmount = false;
                    _updateAmountBasedOnCurrency();
                  }),
                )),
              ),
              const SizedBox(height: 25),
            ],

            // Shared Data Container
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(25), boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.03), blurRadius: 10)]),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                   Row(children: [
                    Expanded(flex: 2, child: _textField(_amountController, isUSD ? "Monto" : "Monto en $currCode ($currSymbol)", isUSD ? Icons.attach_money_rounded : Icons.account_balance_wallet_outlined, numeric: true, onChanged: (val) => _userHasEditedAmount = true)),
                     const SizedBox(width: 10),
                     Expanded(child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text("Moneda", style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.grey)),
                        const SizedBox(height: 5),
                        if (_payType == 'bank' && _selectedBank != null) 
                          Container(
                             padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12), width: double.infinity, decoration: BoxDecoration(color: const Color(0xFFF1F3F5), borderRadius: BorderRadius.circular(15)),
                             child: Text(_selectedCurrency?['code'] ?? '---', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF1B263B))),
                          )
                        else
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 12), decoration: BoxDecoration(color: const Color(0xFFF1F3F5), borderRadius: BorderRadius.circular(15)),
                            child: DropdownButtonHideUnderline(child: DropdownButton(
                                value: _selectedCurrency, isExpanded: true, 
                                items: _currencies.map((c) => DropdownMenuItem(value: c, child: Text(c['code'].toString(), style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)))).toList(),
                                onChanged: (dynamic v) => setState(() {
                                  _selectedCurrency = v;
                                  _userHasEditedAmount = false;
                                  _updateAmountBasedOnCurrency();
                                }),
                            ))),
                      ],
                    )),
                  ]),

                  if (!isUSD) ...[
                    const SizedBox(height: 15),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                      decoration: BoxDecoration(
                        color: const Color(0xFFE8F5E9),
                        borderRadius: BorderRadius.circular(15),
                        border: Border.all(color: Colors.green.shade300)
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text("Equivalente a pagar:", style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF1B263B))),
                          Text("\$${amountUsd.toStringAsFixed(2)} USD", style: TextStyle(fontWeight: FontWeight.w900, fontSize: 16, color: Colors.green.shade800)),
                        ],
                      ),
                    ),
                  ],
                  
                  const SizedBox(height: 15),
                  InkWell(
                    onTap: _selectDate,
                    child: Container(
                      padding: const EdgeInsets.all(12), decoration: BoxDecoration(color: const Color(0xFFF1F3F5), borderRadius: BorderRadius.circular(15)),
                      child: Row(children: [
                        const Icon(Icons.calendar_month_rounded, color: Colors.grey, size: 20), const SizedBox(width: 12),
                        Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                            const Text("Fecha de Pago", style: TextStyle(fontSize: 9, color: Colors.grey, fontWeight: FontWeight.bold)),
                            Text(DateFormat('dd/MM/yyyy').format(_paymentDate), style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                        ]),
                        const Spacer(), const Icon(Icons.calendar_today_outlined, color: Color(0xFF00B4D8), size: 16),
                      ]),
                    ),
                  ),

                  if (!isUSD) ... [
                    const SizedBox(height: 15),
                    if (_rateOptions.length > 1) ...[
                      const Text("Seleccione Tasa del Día", style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.grey)),
                      const SizedBox(height: 5),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12),
                        decoration: BoxDecoration(color: const Color(0xFFF1F3F5), borderRadius: BorderRadius.circular(15)),
                        child: DropdownButtonHideUnderline(
                          child: DropdownButton(
                            value: _selectedRateOption,
                            isExpanded: true,
                            items: _rateOptions.map((opt) => DropdownMenuItem(
                              value: opt,
                              child: Text(opt['label'].toString(), style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                            )).toList(),
                            onChanged: (dynamic v) => setState(() {
                              _selectedRateOption = v;
                              _rateController.text = v['rate'].toString();
                              _rateTypeLabel = v['label'].toString();
                              _updateAmountBasedOnCurrency();
                            }),
                          ),
                        ),
                      ),
                    ] else ...[
                      _textField(_rateController, "Tasa de Cambio (No Editable)", Icons.trending_up_rounded, numeric: true, readOnly: true),
                      Padding(
                        padding: const EdgeInsets.only(top: 5, left: 5),
                        child: Text(
                          _rateTypeLabel.isNotEmpty ? _rateTypeLabel : "Tasa configurada en el sistema", 
                          style: const TextStyle(fontSize: 10, color: Color(0xFF00B4D8), fontWeight: FontWeight.bold)
                        ),
                      )
                    ],
                  ],

                  if (_payType == 'bank' && _selectedBank != null) ... [
                    const SizedBox(height: 15),
                  _textField(_refController, isVED ? "Referencia (Últimos 5 dígitos)" : "Nro de Referencia", Icons.tag_rounded, numeric: true),
                  const Padding(
                    padding: EdgeInsets.only(top: 4, left: 5),
                    child: Text(
                      "Si el vaucher no tiene referencia, coloque su número de cédula.",
                      style: TextStyle(fontSize: 8, color: Colors.grey, fontStyle: FontStyle.italic, fontWeight: FontWeight.bold)
                    ),
                  ),
                    if (_selectedMethod == 'zelle' || isVED) ... [
                      const SizedBox(height: 15),
                      _textField(_issuerController, "Nombre del Emisor (Titular)", Icons.person_rounded),
                    ],
                  ],
                ],
              ),
            ),
            const SizedBox(height: 25),

            _sectionTitle(_payType == 'cash' ? "Evidencia del Dinero (Foto)" : "Comprobante (Foto)"),
            const SizedBox(height: 15),
            InkWell(
              onTap: _pickImage,
              child: Container(
                width: double.infinity, height: 180, decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(25), border: Border.all(color: Colors.grey.shade200, width: 2)),
                child: _image == null ? Column(mainAxisAlignment: MainAxisAlignment.center, children: [
                  Icon(Icons.camera_enhance_rounded, size: 45, color: _payType == 'cash' ? Colors.green.shade300 : Colors.grey.shade400),
                  const SizedBox(height: 10),
                  Text(_payType == 'cash' ? "FOTO OBLIGATORIA DEL EFECTIVO" : "TOMAR FOTO DEL COMPROBANTE", style: TextStyle(color: _payType == 'cash' ? Colors.green.shade700 : Colors.grey.shade500, fontWeight: FontWeight.w900, fontSize: 11))
                ]) : ClipRRect(borderRadius: BorderRadius.circular(23), child: Image.file(_image!, fit: BoxFit.cover)),
              ),
            ),
            
            const SizedBox(height: 40),
            SizedBox(
              width: double.infinity, height: 65, 
              child: ElevatedButton(
                onPressed: _isLoading ? null : _submit, 
                style: ElevatedButton.styleFrom(
                  backgroundColor: _payType == 'cash' ? Colors.green : const Color(0xFF1B263B), 
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(22)),
                  elevation: 5
                ), 
                child: _isLoading 
                  ? const CircularProgressIndicator(color: Colors.white) 
                  : const Text("AGREGAR PAGO", style: TextStyle(color: Colors.white, fontWeight: FontWeight.w900, fontSize: 18, letterSpacing: 1))
              )
            ),
            const SizedBox(height: 30),
          ],
        ),
      ),
    );
  }

  Widget _sectionTitle(String title) => Padding(
    padding: const EdgeInsets.only(left: 5, bottom: 10),
    child: Text(title, style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 15, color: Color(0xFF1B263B))),
  );
  
  Widget _textField(TextEditingController controller, String label, IconData icon, {bool numeric = false, bool readOnly = false, Function(String)? onChanged}) => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Text(label, style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.grey)),
      const SizedBox(height: 5),
      TextField(
        controller: controller, 
        readOnly: readOnly,
        onChanged: onChanged,
        keyboardType: numeric ? const TextInputType.numberWithOptions(decimal: true) : TextInputType.text, 
        style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: readOnly ? Colors.black87 : Colors.black),
        decoration: InputDecoration(
          prefixIcon: Icon(icon, size: 18, color: readOnly ? Colors.grey : const Color(0xFF00B4D8)), 
          filled: true, 
          fillColor: readOnly ? const Color(0xFFE9ECEF) : const Color(0xFFF1F3F5),
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(15), borderSide: BorderSide.none),
          contentPadding: const EdgeInsets.symmetric(vertical: 12)
        ),
      ),
    ],
  );

  Widget _methodButton(String label, IconData icon, Color color, bool isSelected, VoidCallback onTap) {
    return InkWell(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 20),
        decoration: BoxDecoration(color: isSelected ? color : Colors.white, borderRadius: BorderRadius.circular(20), border: isSelected ? null : Border.all(color: Colors.grey.shade300)),
        child: Column(children: [Icon(icon, color: isSelected ? Colors.white : color, size: 30), const SizedBox(height: 8), Text(label, style: TextStyle(color: isSelected ? Colors.white : const Color(0xFF1B263B), fontWeight: FontWeight.w900, fontSize: 12))]),
      ),
    );
  }
}

class PerformanceDashboardScreen extends StatefulWidget {
  const PerformanceDashboardScreen({super.key});
  @override
  State<PerformanceDashboardScreen> createState() => _PerformanceDashboardScreenState();
}

class _PerformanceDashboardScreenState extends State<PerformanceDashboardScreen> {
  bool _isLoading = true;
  Map<String, dynamic> _data = {};
  String _baseUrl = "";

  @override
  void initState() { super.initState(); _load(); }

  _load() async {
    final prefs = await SharedPreferences.getInstance();
    _baseUrl = prefs.getString('base_url') ?? "";
    final token = prefs.getString('token');
    try {
      final res = await http.get(Uri.parse('$_baseUrl/api/seller/dashboard'), headers: {
        'Authorization': 'Bearer $token', 
        'Accept': 'application/json',
        'X-Device-Token': prefs.getString('device_token') ?? ''
      });
      if (res.statusCode == 200) setState(() => _data = json.decode(res.body)['data']);
    } catch (e) { debugPrint("Dashboard Err: $e"); }
    finally { if (mounted) setState(() => _isLoading = false); }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) return const Scaffold(body: Center(child: CircularProgressIndicator()));
    final m = _data['metrics'] ?? {};
    final double sales = (m['monthly_sales'] ?? 0).toDouble();
    final double coll = (m['monthly_collections'] ?? 0).toDouble();
    final double debt = (m['total_debt'] ?? 0).toDouble();
    final double commPending = (m['commissions_earned_pending'] ?? 0).toDouble();
    final double commPaid = (m['commissions_paid_this_month'] ?? 0).toDouble();
    final double goal = (m['monthly_goal'] ?? 0).toDouble();
    final double progress = (m['goal_progress_percent'] ?? 0).toDouble();
    final int count = m['sales_count'] ?? 0;

    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FA),
      appBar: AppBar(
        title: const Text('Mi Rendimiento', style: TextStyle(fontWeight: FontWeight.w900, fontSize: 18)),
        backgroundColor: Colors.white, elevation: 0, iconTheme: const IconThemeData(color: Color(0xFF00B4D8)),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          children: [
            // Month Header
            Container(
              padding: const EdgeInsets.all(20), width: double.infinity,
              decoration: BoxDecoration(
                gradient: const LinearGradient(colors: [Color(0xFF1B263B), Color(0xFF415A77)]),
                borderRadius: BorderRadius.circular(30),
              ),
              child: Column(children: [
                Text(_data['month_name']?.toString().toUpperCase() ?? 'MES ACTUAL', style: const TextStyle(color: Colors.white70, fontSize: 12, fontWeight: FontWeight.bold, letterSpacing: 2)),
                const SizedBox(height: 10),
                const Text('Progreso de Meta', style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w900)),
              ]),
            ),
            const SizedBox(height: -30),
            
            // Goal Circular Card
            Container(
               margin: const EdgeInsets.symmetric(horizontal: 20),
               padding: const EdgeInsets.all(30),
               decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(35), boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 20, offset: const Offset(0, 10))]),
               child: Column(children: [
                 SizedBox(
                   width: 150, height: 150,
                   child: Stack(
                     fit: StackFit.expand,
                     children: [
                        CircularProgressIndicator(value: (progress / 100).clamp(0.0, 1.0), strokeWidth: 12, backgroundColor: Colors.grey.shade100, color: const Color(0xFF00B4D8)),
                        Center(child: Column(mainAxisSize: MainAxisSize.min, children: [
                           Text('${progress.toStringAsFixed(1)}%', style: const TextStyle(fontSize: 28, fontWeight: FontWeight.w900, color: Color(0xFF1B263B))),
                           const Text('LOGRADO', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.grey)),
                        ])),
                     ],
                   ),
                 ),
                 const SizedBox(height: 25),
                 _rowInfo("Ventas del Mes", "\$${sales.toStringAsFixed(2)}", isBold: true),
                        Row(children: [
                    Expanded(child: _miniInfo("Cobranza Mes", "\$${coll.toStringAsFixed(2)}", Colors.green)),
                    const SizedBox(width: 10),
                    Expanded(child: InkWell(
                      onTap: () => Navigator.push(context, MaterialPageRoute(builder: (context) => const DebtDetailScreen())),
                      child: _miniInfo("Cartera Total", "\$${debt.toStringAsFixed(2)}", Colors.orange)
                    )),
                 ]),
                 const Divider(height: 30),
                 _rowInfo("Meta Mensual", goal > 0 ? "\$${goal.toStringAsFixed(2)}" : "Sin Meta"),
               ]),
            ),

            const SizedBox(height: 25),
            
            // Stats Row
            Row(children: [
               Expanded(child: _statCard("Comis. por Cobrar", "\$${commPending.toStringAsFixed(2)}", Icons.account_balance_wallet_rounded, const Color(0xFF2E7D32), onTap: () => Navigator.push(context, MaterialPageRoute(builder: (context) => const CommissionDetailScreen())))),
               const SizedBox(width: 15),
               Expanded(child: _statCard("Comis. Pagadas", "\$${commPaid.toStringAsFixed(2)}", Icons.check_circle_rounded, const Color(0xFF415A77), onTap: () => Navigator.push(context, MaterialPageRoute(builder: (context) => const CommissionDetailScreen())))),
            ]),
            
            const SizedBox(height: 25),
            Row(children: [
               Expanded(child: _statCard("Facturas del Mes", "$count", Icons.assignment_turned_in_rounded, const Color(0xFF00B4D8))),
            ]),

            const SizedBox(height: 25),
            Container(
              padding: const EdgeInsets.all(20), decoration: BoxDecoration(color: const Color(0xFFE9ECEF), borderRadius: BorderRadius.circular(20)),
              child: const Row(children: [
                Icon(Icons.info_outline, color: Colors.blueGrey, size: 20),
                SizedBox(width: 15),
                Expanded(child: Text("Las comisiones se ganan cuando el cliente paga y se liquidan según cronograma de pagos al vendedor.", style: TextStyle(fontSize: 11, color: Colors.blueGrey, fontStyle: FontStyle.italic))),
              ]),
            )
          ],
        ),
      ),
    );
  }

  Widget _miniInfo(String label, String value, Color color) => Container(
    padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 12),
    decoration: BoxDecoration(color: color.withOpacity(0.05), borderRadius: BorderRadius.circular(15), border: Border.all(color: color.withOpacity(0.1))),
    child: Column(children: [
        Text(label, style: TextStyle(color: color, fontSize: 10, fontWeight: FontWeight.bold)),
        const SizedBox(height: 4),
        Text(value, style: TextStyle(color: color.withOpacity(0.8), fontSize: 12, fontWeight: FontWeight.w900)),
    ]),
  );

  Widget _rowInfo(String label, String value, {bool isBold = false}) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 8),
    child: Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
      Text(label, style: const TextStyle(color: Colors.grey, fontWeight: FontWeight.bold, fontSize: 13)),
      Text(value, style: TextStyle(fontWeight: isBold ? FontWeight.w900 : FontWeight.bold, fontSize: 15, color: const Color(0xFF1B263B))),
    ]),
  );

  Widget _statCard(String title, String val, IconData icon, Color col, {VoidCallback? onTap}) => InkWell(
    onTap: onTap,
    borderRadius: BorderRadius.circular(25),
    child: Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(25), boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.02), blurRadius: 10)]),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Icon(icon, color: col, size: 28),
              if (onTap != null) const Icon(Icons.arrow_forward_ios_rounded, size: 12, color: Colors.grey),
            ],
          ),
          const SizedBox(height: 15),
          Text(title, style: const TextStyle(color: Colors.grey, fontWeight: FontWeight.bold, fontSize: 11)),
          const SizedBox(height: 5),
          Text(val, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: Color(0xFF1B263B))),
      ]),
    ),
  );
}

// --- NEW SCREENS FOR PHASE 2.1 ---

class CommissionDetailScreen extends StatefulWidget {
  const CommissionDetailScreen({super.key});
  @override
  State<CommissionDetailScreen> createState() => _CommissionDetailScreenState();
}

class _CommissionDetailScreenState extends State<CommissionDetailScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  bool _isLoading = true;
  List<dynamic> _pending = [];
  List<dynamic> _paid = [];
  Map<String, dynamic> _summary = {};

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _load();
  }

  _load() async {
    final prefs = await SharedPreferences.getInstance();
    final baseUrl = prefs.getString('base_url') ?? "";
    final token = prefs.getString('token');
    try {
      final res = await http.get(Uri.parse('$baseUrl/api/seller/dashboard/commissions'), headers: {
        'Authorization': 'Bearer $token', 
        'Accept': 'application/json',
        'X-Device-Token': prefs.getString('device_token') ?? ''
      });
      if (res.statusCode == 200) {
        final data = json.decode(res.body)['data'];
        setState(() {
          _pending = data['pending'];
          _paid = data['paid'];
          _summary = data['summary'];
        });
      }
    } catch (e) { debugPrint("Comm Err: $e"); }
    finally { if (mounted) setState(() => _isLoading = false); }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Detalle de Comisiones', style: TextStyle(fontWeight: FontWeight.bold)),
        bottom: TabBar(
          controller: _tabController,
          labelColor: const Color(0xFF1B263B),
          unselectedLabelColor: Colors.grey,
          indicatorColor: const Color(0xFF00B4D8),
          tabs: const [Tab(text: 'PENDIENTES'), Tab(text: 'PAGADAS')],
        ),
      ),
      body: Column(
        children: [
           if (!_isLoading) Container(
             padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 15),
             decoration: BoxDecoration(color: Colors.white, border: Border(bottom: BorderSide(color: Colors.grey.shade100))),
             child: Row(
               mainAxisAlignment: MainAxisAlignment.spaceBetween,
               children: [
                 _statMini("Pendiente Mes", "\$${_summary['pending_total'] ?? '0.00'}", Colors.orange),
                 Container(width: 1, height: 30, color: Colors.grey.shade200),
                 _statMini("Pagado Mes", "\$${_summary['paid_total'] ?? '0.00'}", Colors.green),
               ],
             ),
           ),
           Expanded(
             child: _isLoading ? const Center(child: CircularProgressIndicator()) : TabBarView(
               controller: _tabController,
               children: [
                 _buildList(_pending, "No hay comisiones pendientes", "Todas tus comisiones han sido pagadas.", Icons.account_balance_wallet_outlined, isPaidTab: false),
                 _buildList(_paid, "Sin cobros este mes", "Aún no has recibido pagos de comisiones en este periodo.", Icons.history_edu_rounded, isPaidTab: true),
               ],
             ),
           ),
        ],
      ),
    );
  }

  Widget _statMini(String lbl, String val, Color col) => Column(children: [Text(lbl, style: const TextStyle(fontSize: 10, color: Colors.grey, fontWeight: FontWeight.bold)), Text(val, style: TextStyle(fontWeight: FontWeight.w900, fontSize: 16, color: col))]);

  Widget _buildList(List<dynamic> list, String title, String emptyMsg, IconData icon, {required bool isPaidTab}) {
    if (list.isEmpty) return Center(child: Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        Icon(icon, size: 60, color: Colors.grey.withOpacity(0.3)),
        const SizedBox(height: 15),
        Text(title, style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.grey)),
        const SizedBox(height: 5),
        Text(emptyMsg, style: const TextStyle(color: Colors.grey, fontSize: 12), textAlign: TextAlign.center),
      ],
    ));
    return ListView.builder(
      padding: const EdgeInsets.all(15),
      itemCount: list.length,
      itemBuilder: (context, i) {
        final item = list[i];
        return Card(
          elevation: 0, margin: const EdgeInsets.only(bottom: 10),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15), side: BorderSide(color: Colors.grey.shade100)),
          child: ListTile(
            contentPadding: const EdgeInsets.symmetric(horizontal: 15, vertical: 8),
            title: Text(item['customer_name'], style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF1B263B))),
            subtitle: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Factura: ${item['invoice_number']}', style: const TextStyle(fontSize: 11)),
                const SizedBox(height: 2),
                Text(
                  isPaidTab ? 'Cobrado el: ${item['paid_at']}' : 'Vendido el: ${item['date']}',
                  style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: isPaidTab ? Colors.green : Colors.grey),
                ),
              ],
            ),
            trailing: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Text('\$${item['commission_amount']}', style: const TextStyle(fontWeight: FontWeight.w900, color: Color(0xFF1B263B), fontSize: 15)),
                Text('${item['commission_percent']}%', style: const TextStyle(fontSize: 10, color: Colors.grey, fontWeight: FontWeight.bold)),
              ],
            ),
          ),
        );
      },
    );
  }
}

class DebtDetailScreen extends StatefulWidget {
  const DebtDetailScreen({super.key});
  @override
  State<DebtDetailScreen> createState() => _DebtDetailScreenState();
}

class _DebtDetailScreenState extends State<DebtDetailScreen> {
  bool _isLoading = true;
  List<dynamic> _list = [];
  Map<String, dynamic> _summary = {};

  @override
  void initState() { super.initState(); _load(); }

  _load() async {
    final prefs = await SharedPreferences.getInstance();
    final baseUrl = prefs.getString('base_url') ?? "";
    final token = prefs.getString('token');
    try {
      final res = await http.get(Uri.parse('$baseUrl/api/seller/dashboard/debt'), headers: {
        'Authorization': 'Bearer $token', 
        'Accept': 'application/json',
        'X-Device-Token': prefs.getString('device_token') ?? ''
      });
      if (res.statusCode == 200) {
        final data = json.decode(res.body)['data'];
        setState(() {
          _list = data['debt_list'];
          _summary = data['summary'];
        });
      }
    } catch (e) { debugPrint("Debt Err: $e"); }
    finally { if (mounted) setState(() => _isLoading = false); }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Cartera y Vencimientos', style: TextStyle(fontWeight: FontWeight.bold))),
      body: _isLoading ? const Center(child: CircularProgressIndicator()) : Column(
        children: [
          Container(
            padding: const EdgeInsets.all(20), color: Colors.orange.shade50,
            child: Row(mainAxisAlignment: MainAxisAlignment.spaceAround, children: [
              _sumItem("Total Deuda", "\$${_summary['total_debt_amount']}"),
              _sumItem("Vencidas", "${_summary['total_overdue_count']}"),
            ]),
          ),
          Expanded(child: ListView.builder(
            padding: const EdgeInsets.all(15),
            itemCount: _list.length,
            itemBuilder: (context, i) {
              final item = _list[i];
              Color color = Colors.blue;
              if (item['aging_color'] == 'orange') color = Colors.orange;
              if (item['aging_color'] == 'red') color = Colors.red;

              return Card(
                elevation: 0, margin: const EdgeInsets.only(bottom: 12),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15), side: BorderSide(color: color.withOpacity(0.3))),
                child: Padding(
                  padding: const EdgeInsets.all(15),
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Expanded(child: Text(item['customer_name'], style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14))),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4), 
                          decoration: BoxDecoration(color: color.withOpacity(0.1), borderRadius: BorderRadius.circular(10)), 
                          child: Text(
                            item['overdue_days'] > 0 ? '${item['overdue_days']} días mora' : 'A TIEMPO', 
                            style: TextStyle(color: color, fontWeight: FontWeight.bold, fontSize: 10)
                          )
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text('Factura: ${item['invoice_number']}', style: const TextStyle(color: Colors.grey, fontSize: 12)),
                        Text('\$${item['remaining_debt_usd']}', style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 16, color: Color(0xFF1B263B))),
                      ],
                    ),
                    const Divider(height: 20),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('COMISIÓN PROYECTADA', style: TextStyle(color: Colors.grey.shade600, fontSize: 9, fontWeight: FontWeight.bold)),
                            Text('\$${item['projected_commission_amount']} (${item['projected_commission_percent']}%)', style: TextStyle(color: item['comm_status'] == 'lost' ? Colors.red : Colors.green, fontWeight: FontWeight.w900, fontSize: 12)),
                          ],
                        ),
                        if (item['days_left_tier'] != null && item['comm_status'] != 'lost') Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                          decoration: BoxDecoration(color: Colors.red.shade50, borderRadius: BorderRadius.circular(8)),
                          child: Text(
                            '¡FALTAN ${item['days_left_tier']} DÍAS!', 
                            style: const TextStyle(color: Colors.red, fontWeight: FontWeight.w900, fontSize: 10)
                          ),
                        ),
                        if (item['comm_status'] == 'lost') const Text('COMISIÓN PERDIDA', style: TextStyle(color: Colors.red, fontWeight: FontWeight.w900, fontSize: 10)),
                      ],
                    ),
                  ]),
                ),
              );
            },
          )),
        ],
      ),
    );
  }

  Widget _sumItem(String label, String val) => Column(children: [Text(label, style: const TextStyle(fontSize: 10, color: Colors.grey)), Text(val, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18))]);
}

class PaymentAuditScreen extends StatefulWidget {
  const PaymentAuditScreen({super.key});
  @override
  State<PaymentAuditScreen> createState() => _PaymentAuditScreenState();
}

class _PaymentAuditScreenState extends State<PaymentAuditScreen> {
  bool _isLoading = true;
  List<dynamic> _list = [];

  @override
  void initState() { super.initState(); _load(); }

  _load() async {
    final prefs = await SharedPreferences.getInstance();
    final baseUrl = prefs.getString('base_url') ?? "";
    final token = prefs.getString('token');
    try {
      final res = await http.get(Uri.parse('$baseUrl/api/payments/history/global'), headers: {
        'Authorization': 'Bearer $token', 
        'Accept': 'application/json',
        'X-Device-Token': prefs.getString('device_token') ?? ''
      });
      if (res.statusCode == 200) setState(() => _list = json.decode(res.body)['data']);
    } catch (e) { debugPrint("Audit Err: $e"); }
    finally { if (mounted) setState(() => _isLoading = false); }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Auditoría de Pagos Subidos', style: TextStyle(fontWeight: FontWeight.bold))),
      body: _isLoading ? const Center(child: CircularProgressIndicator()) : ListView.builder(
        padding: const EdgeInsets.all(15),
        itemCount: _list.length,
        itemBuilder: (context, i) {
          final p = _list[i];
          Color statusCol = Colors.grey;
          IconData icon = Icons.timer_outlined;
          if (p['status'] == 'approved' || p['status'] == 'settled') { statusCol = Colors.green; icon = Icons.check_circle; }
          if (p['status'] == 'rejected') { statusCol = Colors.red; icon = Icons.cancel; }

          return Card(
            margin: const EdgeInsets.only(bottom: 12), elevation: 0,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15), side: BorderSide(color: Colors.grey.shade200)),
            child: Padding(
              padding: const EdgeInsets.all(15),
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
                  Expanded(child: Text(p['customer_name'], style: const TextStyle(fontWeight: FontWeight.bold))),
                  Text('\$${p['amount']} ${p['currency']}', style: const TextStyle(fontWeight: FontWeight.bold)),
                ]),
                const SizedBox(height: 5),
                Text('Factura: ${p['invoice_number']} | ${p['date']}', style: const TextStyle(color: Colors.grey, fontSize: 11)),
                const Divider(height: 20),
                Row(children: [
                  Icon(icon, color: statusCol, size: 16),
                  const SizedBox(width: 8),
                  Text(p['status'].toString().toUpperCase(), style: TextStyle(color: statusCol, fontWeight: FontWeight.bold, fontSize: 12)),
                  const Spacer(),
                  Text(p['method'].toString().toUpperCase(), style: const TextStyle(fontSize: 10, color: Colors.grey)),
                ]),
                if (p['status'] == 'rejected' && p['rejection_reason'] != null) Container(
                  margin: const EdgeInsets.only(top: 10), padding: const EdgeInsets.all(10),
                  width: double.infinity, decoration: BoxDecoration(color: Colors.red.shade50, borderRadius: BorderRadius.circular(10)),
                  child: Text('Motivo: ${p['rejection_reason']}', style: const TextStyle(color: Colors.red, fontSize: 11, fontWeight: FontWeight.bold)),
                ),
              ]),
            ),
          );
        },
      ),
    );
  }
}
