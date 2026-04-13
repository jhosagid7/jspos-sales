import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'dart:async';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:cached_network_image/cached_network_image.dart';

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
        textTheme: GoogleFonts.outfitTextTheme(),
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
  Customer({required this.id, required this.name});
  factory Customer.fromJson(Map<String, dynamic> json) => Customer(id: int.parse(json['id'].toString()), name: json['name']);
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

  @override
  void initState() { super.initState(); _loadSettings(); }
  _loadSettings() async { final prefs = await SharedPreferences.getInstance(); setState(() { _baseUrl = prefs.getString('base_url') ?? 'http://192.168.194.66'; _emailController.text = prefs.getString('last_email') ?? ''; }); }

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
    try {
      final response = await http.post(
        Uri.parse('$_baseUrl/api/login'),
        headers: {'Accept': 'application/json'},
        body: {'email': _emailController.text, 'password': _passwordController.text, 'device_name': 'MobileApp'},
      ).timeout(const Duration(seconds: 15));

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        final prefs = await SharedPreferences.getInstance();
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
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e')));
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
      final response = await http.get(Uri.parse('$baseUrl/api/user'), headers: {'Authorization': 'Bearer $token', 'Accept': 'application/json'}).timeout(const Duration(seconds: 10));
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
                    _menuCard('CLIENTES', Icons.people_alt_rounded, const Color(0xFFF9C74F), () {}),
                    _menuCard('CONFIG', Icons.settings_suggest_rounded, const Color(0xFFF94144), () {}),
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

  @override
  void initState() { 
    super.initState(); 
    if (widget.initialCart != null && widget.initialCart!.isNotEmpty) {
       _cart.clear();
       _cart.addAll(widget.initialCart!);
    }
    if (widget.initialCustomer != null) _selectedCustomer = widget.initialCustomer;
    if (widget.initialNotes != null) _notesController.text = widget.initialNotes!;
    _init(); 
  }
  _init() async { 
    final prefs = await SharedPreferences.getInstance(); 
    _baseUrl = prefs.getString('base_url') ?? "http://192.168.194.66"; 
    String dlStr = prefs.getString('deadline') ?? "";
    if (dlStr.isNotEmpty) _deadline = DateTime.tryParse(dlStr);
    _isDeadlineActive = prefs.getBool('deadline_active') ?? false;
    await _fetchCustomers(); 
    await _fetchProducts(); 
  }

  bool get _isExpired => _isDeadlineActive && _deadline != null && DateTime.now().isAfter(_deadline!);

  Future<void> _fetchCustomers() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');
      final response = await http.get(Uri.parse('$_baseUrl/api/customers'), headers: {'Authorization': 'Bearer $token', 'Accept': 'application/json'}).timeout(const Duration(seconds: 15));
      if (response.statusCode == 200) setState(() { _customers.clear(); _customers.addAll((json.decode(response.body) as List).map((e) => Customer.fromJson(e)).toList()); });
    } catch (e) { debugPrint("Err Clientes: $e"); }
  }

  Future<void> _fetchProducts([String search = '']) async {
    setState(() { _isLoading = true; _errorMessage = null; });
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');
      String url = '$_baseUrl/api/products?search=$search';
      if (_selectedCustomer != null) url += '&customer_id=${_selectedCustomer!.id}';
      final response = await http.get(Uri.parse(url), headers: {'Authorization': 'Bearer $token', 'Accept': 'application/json'}).timeout(const Duration(seconds: 15));
      if (response.statusCode == 200) setState(() { _products.clear(); _products.addAll((json.decode(response.body) as List).map((e) => Product.fromJson(e)).toList()); });
      else setState(() => _errorMessage = "Err Server: ${response.statusCode}");
    } catch (e) { setState(() => _errorMessage = "Err: $e"); }
    finally { setState(() => _isLoading = false); }
  }

  Future<void> _submitOrder() async {
    if (_isExpired) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('El periodo de pedidos ha terminado.'), backgroundColor: Colors.red));
      return;
    }
    if (_cart.isEmpty) return;
    setState(() => _isLoading = true);
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');
      if (_selectedCustomer == null) { ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Seleccione un cliente'))); return; }

      final items = _cart.where((i) => i.customer.id == _selectedCustomer!.id).map((i) => {'product_id': i.product.id, 'quantity': i.quantity, 'price': i.product.price}).toList();

      final body = {
        'customer_id': _selectedCustomer!.id, 
        'items': items, 
        'notes': _notesController.text
      };
      if (widget.originalOrderId != null) body['original_order_id'] = widget.originalOrderId!;

      final response = await http.post(
        Uri.parse('$_baseUrl/api/orders'),
        headers: {'Authorization': 'Bearer $token', 'Accept': 'application/json', 'Content-Type': 'application/json'},
                body: json.encode(body),
      );

      if (response.statusCode == 200) {
        setState(() { _cart.clear(); _selectedCustomer = null; _notesController.clear(); });
        if (mounted) { ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('PRE-ORDEN GUARDADA EXITOSAMENTE'), backgroundColor: Colors.green)); Navigator.pop(context); }
      } else {
        final err = json.decode(response.body)['message'] ?? response.body;
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $err')));
      }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error de red: $e')));
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
          hintText: 'Buscar producto...', 
          hintStyle: TextStyle(color: Colors.grey.shade400),
          prefixIcon: const Icon(Icons.search_rounded, color: Color(0xFF00B4D8)), 
          filled: true,
          fillColor: Colors.white,
          contentPadding: const EdgeInsets.symmetric(vertical: 15),
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(20), borderSide: BorderSide.none),
          enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(20), borderSide: BorderSide.none),
        ), 
        onSubmitted: (v) => _fetchProducts(v)
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
        Expanded(child: ListView.separated(separatorBuilder: (c, i) => const Divider(height: 1), itemCount: filtered.length, itemBuilder: (context, i) => ListTile(contentPadding: const EdgeInsets.symmetric(vertical: 5), leading: Container(padding: const EdgeInsets.all(10), decoration: BoxDecoration(color: const Color(0xFF00B4D8).withOpacity(0.1), shape: BoxShape.circle), child: const Icon(Icons.person_rounded, color: Color(0xFF00B4D8), size: 20)), title: Text(filtered[i].name, style: const TextStyle(fontWeight: FontWeight.bold)), onTap: () { setState(() => _selectedCustomer = filtered[i]); _fetchProducts(); Navigator.pop(context); })))
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
                if (_cart.isNotEmpty && !_isExpired) IconButton(icon: const Icon(Icons.delete_sweep_rounded, color: Colors.red, size: 28), onPressed: () { setState(() => _cart.clear()); setModalState(() {}); Navigator.pop(context); }),
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
                      InkWell(onTap: () { setState(() { if (_cart[i].quantity > 1.0) { _cart[i].quantity -= 1.0; } else { _cart.removeAt(i); } }); setModalState(() {}); if (_cart.isEmpty) Navigator.pop(context); }, child: const Icon(Icons.remove_circle_outline_rounded, color: Colors.orange)),
                      const SizedBox(width: 10),
                      InkWell(
                        onTap: () { 
                          // Cart Inventory Validation
                          if (_cart[i].product.checkReservation && (_cart[i].quantity + 1.0) > _cart[i].product.availableStock) {
                             ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('No hay más stock disponible (Reservado)'), backgroundColor: Colors.orange));
                             return;
                          }
                          setState(() => _cart[i].quantity += 1.0); 
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

    final token = prefs.getString('token');
    try {
      final response = await http.get(Uri.parse('$_baseUrl/api/orders'), headers: {'Authorization': 'Bearer $token', 'Accept': 'application/json'}).timeout(const Duration(seconds: 15));
      if (response.statusCode == 200) {
        List all = json.decode(response.body);
        setState(() => _orders = all.where((o) => o['status'] != 'processed').toList());
      }
    } catch (e) { debugPrint("Err Historial: $e"); }
    finally { setState(() => _isLoading = false); }
  }

  bool get _isExpired => _isDeadlineActive && _deadline != null && DateTime.now().isAfter(_deadline!);

  Future<void> _sendToOffice(int id) async {
    if (_isExpired) { ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Periodo de envío cerrado.'))); return; }
    setState(() => _isLoading = true);
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token');
    try {
      final response = await http.post(Uri.parse('$_baseUrl/api/orders/$id/send'), headers: {'Authorization': 'Bearer $token', 'Accept': 'application/json'});
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
      final response = await http.delete(Uri.parse('$_baseUrl/api/orders/$id'), headers: {'Authorization': 'Bearer $token', 'Accept': 'application/json'});
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
      final response = await http.get(Uri.parse('$_baseUrl/api/orders/$id/logs'), headers: {'Authorization': 'Bearer $token', 'Accept': 'application/json'});
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
