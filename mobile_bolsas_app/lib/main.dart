import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:package_info_plus/package_info_plus.dart';
import 'package:intl/intl.dart';
import 'dart:math';

void main() {
  runApp(const BolsasApp());
}

class BolsasApp extends StatelessWidget {
  const BolsasApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'JSPOS Bolsas',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF1B263B), primary: const Color(0xFF1B263B)),
        useMaterial3: true,
        textTheme: GoogleFonts.outfitTextTheme(),
      ),
      home: const LoginScreen(),
    );
  }
}

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
  String _baseUrl = "";
  String _appVersion = "";
  String _deviceToken = "";

  @override
  void initState() {
    super.initState();
    _init();
  }

  _init() async {
    final prefs = await SharedPreferences.getInstance();
    final pkg = await PackageInfo.fromPlatform();

    String token = prefs.getString('device_token') ?? '';
    if (token.isEmpty) {
      token = 'BOLSAS-${DateTime.now().millisecondsSinceEpoch}-${_generateRandomString(4)}';
      await prefs.setString('device_token', token);
    }

    setState(() {
      _baseUrl = prefs.getString('base_url') ?? '';
      _emailController.text = prefs.getString('last_email') ?? '';
      _appVersion = pkg.version;
      _deviceToken = token;
    });
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
            ListTile(
              title: const Text('VPN ZeroTier'),
              subtitle: const Text('http://192.168.194.66'),
              onTap: () => controller.text = 'http://192.168.194.66',
            ),
            ListTile(
              title: const Text('IP Local'),
              subtitle: const Text('http://192.168.1.100'),
              onTap: () => controller.text = 'http://192.168.1.100',
            ),
            const Divider(),
            TextField(
              controller: controller,
              decoration: const InputDecoration(labelText: 'IP/URL Personalizada'),
            ),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('CERRAR')),
          ElevatedButton(
            onPressed: () async {
              final prefs = await SharedPreferences.getInstance();
              await prefs.setString('base_url', controller.text);
              setState(() => _baseUrl = controller.text);
              if (mounted) Navigator.pop(context);
            },
            child: const Text('GUARDAR'),
          ),
        ],
      ),
    );
  }

  Future<void> _login() async {
    if (_baseUrl.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Por favor configure la URL del servidor primero')),
      );
      return;
    }
    setState(() => _isLoading = true);
    try {
      final response = await http.post(
        Uri.parse('$_baseUrl/api/login'),
        headers: {
          'Accept': 'application/json',
          'X-Device-Token': _deviceToken,
        },
        body: {
          'email': _emailController.text,
          'password': _passwordController.text,
          'device_name': 'Mobile (Bolsas)',
          'app_type': 'bolsas',
        },
      ).timeout(const Duration(seconds: 15));

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        final prefs = await SharedPreferences.getInstance();
        await prefs.setInt('user_id', data['user']['id'] ?? 0);
        await prefs.setString('token', data['token'] ?? '');
        await prefs.setString('user_name', data['user']['name'] ?? 'Operador Bolsas');
        await prefs.setString('last_email', _emailController.text);

        if (mounted) {
          Navigator.pushReplacement(
            context,
            MaterialPageRoute(builder: (context) => DashboardScreen(baseUrl: _baseUrl)),
          );
        }
      } else {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Credenciales incorrectas')),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error de conexión: $e')),
        );
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
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [Color(0xFF0D1B2A), Color(0xFF1B263B)],
          ),
        ),
        child: Column(
          children: [
            const SizedBox(height: 60),
            Row(
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                IconButton(
                  onPressed: _showSettings,
                  icon: const Icon(Icons.settings, color: Colors.white70),
                )
              ],
            ),
            const Icon(Icons.precision_manufacturing_rounded, size: 100, color: Colors.amberAccent),
            const SizedBox(height: 10),
            const Text(
              'JSPOS Bolsas',
              style: TextStyle(color: Colors.white, fontSize: 36, fontWeight: FontWeight.bold),
            ),
            const Text(
              'Levantamiento de Producción de Bolsas',
              style: TextStyle(color: Colors.white70, fontSize: 16),
            ),
            const Spacer(),
            Container(
              padding: const EdgeInsets.all(30),
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.vertical(top: Radius.circular(30)),
              ),
              child: Column(
                children: [
                  TextField(
                    controller: _emailController,
                    decoration: InputDecoration(
                      labelText: 'Usuario (Email)',
                      border: const OutlineInputBorder(),
                      prefixIcon: const Icon(Icons.person),
                      suffixIcon: IconButton(
                        icon: const Icon(Icons.clear),
                        onPressed: () => _emailController.clear(),
                      ),
                    ),
                  ),
                  const SizedBox(height: 20),
                  TextField(
                    controller: _passwordController,
                    obscureText: _obscurePassword,
                    decoration: InputDecoration(
                      labelText: 'Contraseña',
                      border: const OutlineInputBorder(),
                      prefixIcon: const Icon(Icons.lock),
                      suffixIcon: IconButton(
                        icon: Icon(_obscurePassword ? Icons.visibility_off : Icons.visibility),
                        onPressed: () => setState(() => _obscurePassword = !_obscurePassword),
                      ),
                    ),
                  ),
                  const SizedBox(height: 30),
                  SizedBox(
                    width: double.infinity,
                    height: 55,
                    child: ElevatedButton(
                      onPressed: _isLoading ? null : _login,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF1B263B),
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      ),
                      child: _isLoading
                          ? const CircularProgressIndicator(color: Colors.white)
                          : const Text('ENTRAR', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                    ),
                  ),
                  const SizedBox(height: 15),
                  Text(
                    'Servidor: $_baseUrl  •  Versión Bolsas: $_appVersion',
                    style: const TextStyle(fontSize: 10, color: Colors.grey),
                  ),
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
  final String baseUrl;
  const DashboardScreen({super.key, required this.baseUrl});
  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  String _userName = "";
  late String _baseUrl;
  String _appVersion = "";

  @override
  void initState() {
    super.initState();
    _baseUrl = widget.baseUrl;
    _loadUser();
  }

  _loadUser() async {
    final prefs = await SharedPreferences.getInstance();
    final pkg = await PackageInfo.fromPlatform();
    setState(() {
      _appVersion = pkg.version;
      _userName = prefs.getString('user_name') ?? "Operador Bolsas";
    });
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
    return Scaffold(
      backgroundColor: const Color(0xFFF4F6F9),
      appBar: AppBar(
        backgroundColor: const Color(0xFF1B263B),
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.white),
        title: const Text(
          'Fábrica de Bolsas',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.w900, fontSize: 20),
        ),
      ),
      drawer: Drawer(
        child: Column(
          children: [
            UserAccountsDrawerHeader(
              decoration: const BoxDecoration(color: Color(0xFF1B263B)),
              accountName: Text(_userName, style: const TextStyle(fontWeight: FontWeight.bold)),
              accountEmail: const Text("Operador de Fábrica"),
              currentAccountPicture: const CircleAvatar(
                backgroundColor: Colors.white,
                child: Icon(Icons.precision_manufacturing_rounded, color: Color(0xFF1B263B), size: 40),
              ),
            ),
            ListTile(
              leading: const Icon(Icons.logout, color: Colors.red),
              title: const Text('Cerrar Sesión'),
              onTap: _logout,
            ),
            const Spacer(),
            const Divider(height: 1),
            Padding(
              padding: const EdgeInsets.only(bottom: 25, top: 15),
              child: Text('v$_appVersion', style: const TextStyle(color: Colors.grey, fontSize: 11)),
            ),
          ],
        ),
      ),
      body: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: double.infinity,
              margin: const EdgeInsets.all(15),
              padding: const EdgeInsets.symmetric(horizontal: 25, vertical: 30),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFF1B263B), Color(0xFF415A77)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(20),
                boxShadow: [
                  BoxShadow(
                    color: const Color(0xFF1B263B).withOpacity(0.3),
                    blurRadius: 15,
                    offset: const Offset(0, 8),
                  )
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Bienvenido(a),', style: TextStyle(color: Colors.white.withOpacity(0.8), fontSize: 16)),
                  const SizedBox(height: 5),
                  Text(
                    _userName,
                    style: const TextStyle(color: Colors.white, fontSize: 24, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 10),
                  const Text(
                    'Levantamiento y registro de lotes de producción diarios.',
                    style: TextStyle(color: Colors.white70, fontSize: 12),
                  ),
                ],
              ),
            ),
            const Padding(
              padding: EdgeInsets.symmetric(horizontal: 25, vertical: 10),
              child: Text(
                'Operaciones',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF1B263B)),
              ),
            ),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20),
              child: Column(
                children: [
                  _menuCard(
                    'Registrar Producción',
                    'Cargar bolsas producidas por día',
                    Icons.add_task_rounded,
                    Colors.blue.shade700,
                    () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(builder: (context) => ProductionScreen(baseUrl: _baseUrl)),
                      );
                    },
                  ),
                  const SizedBox(height: 15),
                  _menuCard(
                    'Historial de Levantamiento',
                    'Ver lotes subidos anteriormente',
                    Icons.history_rounded,
                    Colors.teal.shade700,
                    () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(builder: (context) => ProductionHistoryScreen(baseUrl: _baseUrl)),
                      );
                    },
                  ),
                ],
              ),
            ),
            const SizedBox(height: 50),
          ],
        ),
      ),
    );
  }

  Widget _menuCard(String title, String subtitle, IconData icon, Color color, VoidCallback onTap) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          boxShadow: [
            BoxShadow(color: Colors.black.withOpacity(0.04), blurRadius: 10, offset: const Offset(0, 4))
          ],
        ),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(15),
              decoration: BoxDecoration(color: color.withOpacity(0.1), shape: BoxShape.circle),
              child: Icon(icon, color: color, size: 30),
            ),
            const SizedBox(width: 20),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF1B263B))),
                  const SizedBox(height: 4),
                  Text(subtitle, style: const TextStyle(fontSize: 12, color: Colors.grey)),
                ],
              ),
            ),
            const Icon(Icons.chevron_right, color: Colors.grey),
          ],
        ),
      ),
    );
  }
}

// --- PRODUCT MODEL ---
class ProductSimple {
  final int id;
  final String name;
  final String sku;
  final double cost;
  final bool isVariableQuantity;

  ProductSimple({
    required this.id,
    required this.name,
    required this.sku,
    required this.cost,
    required this.isVariableQuantity,
  });

  factory ProductSimple.fromJson(Map<String, dynamic> json) => ProductSimple(
        id: int.parse(json['id'].toString()),
        name: json['name'],
        sku: json['sku'] ?? '',
        cost: double.tryParse(json['cost']?.toString() ?? '0.0') ?? 0.0,
        isVariableQuantity: json['is_variable_quantity'] == true ||
            json['is_variable_quantity'] == 1 ||
            json['is_variable_quantity'] == '1',
      );
}

// --- CART ENTRY MODEL ---
class ProductionEntry {
  final ProductSimple product;
  double quantity;
  double weight;
  String operatorName;
  DateTime productionDate;
  List<Map<String, dynamic>> metadata; // Contains weights of individual rolls for variables

  ProductionEntry({
    required this.product,
    required this.quantity,
    required this.weight,
    required this.operatorName,
    required this.productionDate,
    required this.metadata,
  });
}

// --- REGISTRAR PRODUCCION SCREEN ---
class ProductionScreen extends StatefulWidget {
  final String baseUrl;
  const ProductionScreen({super.key, required this.baseUrl});
  @override
  State<ProductionScreen> createState() => _ProductionScreenState();
}

class _ProductionScreenState extends State<ProductionScreen> {
  DateTime _productionDate = DateTime.now();
  List<ProductSimple> _availableProducts = [];
  final List<ProductionEntry> _entries = [];
  bool _isLoadingProducts = false;
  bool _isSubmitting = false;
  final _notesController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _fetchProducts();
  }

  Future<void> _fetchProducts() async {
    setState(() => _isLoadingProducts = true);
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');
      final response = await http.get(
        Uri.parse('${widget.baseUrl}/api/bolsas/products'),
        headers: {'Authorization': 'Bearer $token', 'Accept': 'application/json'},
      ).timeout(const Duration(seconds: 15));
      if (response.statusCode == 200) {
        final List data = json.decode(response.body);
        setState(() => _availableProducts = data.map((e) => ProductSimple.fromJson(e)).toList());
      }
    } catch (e) {
      debugPrint('Fetch bags products err: $e');
    } finally {
      setState(() => _isLoadingProducts = false);
    }
  }

  Future<void> _selectDate() async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: _productionDate,
      firstDate: DateTime.now().subtract(const Duration(days: 90)),
      lastDate: DateTime.now().add(const Duration(days: 1)),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: const ColorScheme.light(
              primary: Color(0xFF1B263B),
              onPrimary: Colors.white,
              onSurface: Color(0xFF1B263B),
            ),
          ),
          child: child!,
        );
      },
    );
    if (picked != null && picked != _productionDate) {
      setState(() {
        _productionDate = picked;
      });
    }
  }

  void _showAddProductDialog() {
    ProductSimple? selectedProduct;
    final qtyController = TextEditingController();
    final weightController = TextEditingController();
    final operatorController = TextEditingController();
    String searchQuery = '';
    DateTime itemProductionDate = _productionDate;

    // Variable items local list
    List<Map<String, dynamic>> variableRolls = [];
    final rollWeightController = TextEditingController();
    final rollColorController = TextEditingController();
    final rollBatchController = TextEditingController();

    // Load last operator name from SharedPreferences
    SharedPreferences.getInstance().then((prefs) {
      final lastOp = prefs.getString('last_operator_name') ?? '';
      operatorController.text = lastOp;
    });

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setModal) {
          final filtered = _availableProducts
              .where((p) =>
                  p.name.toLowerCase().contains(searchQuery.toLowerCase()) ||
                  p.sku.toLowerCase() == searchQuery.toLowerCase())
              .toList();

          double totalVariableWeight() {
            return variableRolls.fold(0.0, (sum, roll) => sum + (roll['weight'] ?? 0.0));
          }

          return Container(
            decoration: const BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
            ),
            padding: EdgeInsets.only(
              bottom: MediaQuery.of(ctx).viewInsets.bottom + 20,
              left: 20,
              right: 20,
              top: 20,
            ),
            child: SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Center(
                    child: Container(
                      width: 40,
                      height: 4,
                      decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(2)),
                    ),
                  ),
                  const SizedBox(height: 16),
                  const Text(
                    'Agregar Bolsa Producida',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF1B263B)),
                  ),
                  const SizedBox(height: 16),
                  if (selectedProduct == null) ...[
                    TextField(
                      decoration: InputDecoration(
                        hintText: 'Escriba nombre o lea código QR...',
                        prefixIcon: const Icon(Icons.search),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                      ),
                      onChanged: (v) {
                        setModal(() => searchQuery = v);
                        // Check if scanned value matches exactly
                        final exactMatch = _availableProducts.firstWhere(
                          (p) => p.sku.toUpperCase() == v.trim().toUpperCase(),
                          orElse: () => ProductSimple(id: 0, name: '', sku: '', cost: 0.0, isVariableQuantity: false),
                        );
                        if (exactMatch.id != 0) {
                          setModal(() {
                            selectedProduct = exactMatch;
                            searchQuery = '';
                          });
                        }
                      },
                    ),
                    const SizedBox(height: 12),
                    Container(
                      constraints: const BoxConstraints(maxHeight: 200),
                      decoration: BoxDecoration(
                        border: Border.all(color: Colors.grey.shade200),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: filtered.isEmpty
                          ? const Padding(
                              padding: EdgeInsets.all(20),
                              child: Center(child: Text('No hay productos que coincidan', style: TextStyle(color: Colors.grey))),
                            )
                          : ListView.separated(
                              shrinkWrap: true,
                              itemCount: filtered.length,
                              separatorBuilder: (_, __) => Divider(height: 1, color: Colors.grey.shade100),
                              itemBuilder: (_, i) => ListTile(
                                dense: true,
                                leading: Container(
                                  padding: const EdgeInsets.all(6),
                                  decoration: BoxDecoration(
                                    color: const Color(0xFF1B263B).withOpacity(0.1),
                                    borderRadius: BorderRadius.circular(8),
                                  ),
                                  child: const Icon(Icons.inventory_2_outlined, color: Color(0xFF1B263B), size: 18),
                                ),
                                title: Text(filtered[i].name, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
                                subtitle: Text(filtered[i].sku, style: const TextStyle(fontSize: 11)),
                                onTap: () {
                                  setModal(() {
                                    selectedProduct = filtered[i];
                                    searchQuery = '';
                                  });
                                },
                              ),
                            ),
                    ),
                  ] else ...[
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                      decoration: BoxDecoration(
                        color: const Color(0xFF1B263B).withOpacity(0.08),
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(color: const Color(0xFF1B263B).withOpacity(0.3)),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.check_circle, color: Color(0xFF1B263B), size: 20),
                          const SizedBox(width: 8),
                          Expanded(child: Text(selectedProduct!.name, style: const TextStyle(fontWeight: FontWeight.bold))),
                          GestureDetector(
                            onTap: () => setModal(() {
                              selectedProduct = null;
                              variableRolls.clear();
                              qtyController.clear();
                              weightController.clear();
                            }),
                            child: const Icon(Icons.close, size: 18, color: Colors.grey),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),
                    TextField(
                      controller: operatorController,
                      decoration: InputDecoration(
                        labelText: 'Operador / Fabricante (Nombre o Inicial)',
                        prefixIcon: const Icon(Icons.person_outline),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                    ),
                    const SizedBox(height: 16),
                    InkWell(
                      onTap: () async {
                        final DateTime? picked = await showDatePicker(
                          context: ctx,
                          initialDate: itemProductionDate,
                          firstDate: DateTime.now().subtract(const Duration(days: 90)),
                          lastDate: DateTime.now().add(const Duration(days: 1)),
                          builder: (context, child) {
                            return Theme(
                              data: Theme.of(context).copyWith(
                                colorScheme: const ColorScheme.light(
                                  primary: Color(0xFF1B263B),
                                  onPrimary: Colors.white,
                                  onSurface: Color(0xFF1B263B),
                                ),
                              ),
                              child: child!,
                            );
                          },
                        );
                        if (picked != null) {
                          setModal(() {
                            itemProductionDate = picked;
                          });
                        }
                      },
                      child: InputDecorator(
                        decoration: InputDecoration(
                          labelText: 'Fecha de Elaboración de este Producto',
                          prefixIcon: const Icon(Icons.calendar_today_outlined),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        child: Text(
                          DateFormat('dd / MM / yyyy').format(itemProductionDate),
                          style: const TextStyle(fontSize: 16),
                        ),
                      ),
                    ),
                    const SizedBox(height: 16),
                    if (selectedProduct!.isVariableQuantity) ...[
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: Colors.amber.shade50,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: Colors.amber.shade200),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              'Presentación Variable (Carga de Rollos)',
                              style: TextStyle(fontWeight: FontWeight.bold, color: Colors.amber),
                            ),
                            const SizedBox(height: 8),
                            Row(
                              children: [
                                Expanded(
                                  child: TextField(
                                    controller: rollWeightController,
                                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                                    decoration: const InputDecoration(
                                      labelText: 'Peso del Rollo (Kg)',
                                      border: OutlineInputBorder(),
                                      isDense: true,
                                    ),
                                  ),
                                ),
                                const SizedBox(width: 8),
                                Expanded(
                                  child: TextField(
                                    controller: rollColorController,
                                    decoration: const InputDecoration(
                                      labelText: 'Color (Opcional)',
                                      border: OutlineInputBorder(),
                                      isDense: true,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 8),
                            Row(
                              children: [
                                Expanded(
                                  child: TextField(
                                    controller: rollBatchController,
                                    decoration: const InputDecoration(
                                      labelText: 'Lote (Opcional)',
                                      border: OutlineInputBorder(),
                                      isDense: true,
                                    ),
                                  ),
                                ),
                                const SizedBox(width: 8),
                                ElevatedButton.icon(
                                  onPressed: () {
                                    double w = double.tryParse(rollWeightController.text) ?? 0.0;
                                    if (w > 0) {
                                      setModal(() {
                                        variableRolls.add({
                                          'weight': w,
                                          'color': rollColorController.text.trim(),
                                          'batch': rollBatchController.text.trim(),
                                        });
                                        rollWeightController.clear();
                                        rollColorController.clear();
                                        rollBatchController.clear();
                                      });
                                    }
                                  },
                                  style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF1B263B)),
                                  icon: const Icon(Icons.add, color: Colors.white, size: 16),
                                  label: const Text('AÑADIR', style: TextStyle(color: Colors.white, fontSize: 11)),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 12),
                      if (variableRolls.isNotEmpty) ...[
                        const Text('Rollos Registrados:', style: TextStyle(fontWeight: FontWeight.bold)),
                        const SizedBox(height: 6),
                        Container(
                          constraints: const BoxConstraints(maxHeight: 120),
                          child: ListView.builder(
                            shrinkWrap: true,
                            itemCount: variableRolls.length,
                            itemBuilder: (ctx, idx) {
                              final roll = variableRolls[idx];
                              return Card(
                                margin: const EdgeInsets.only(bottom: 6),
                                child: ListTile(
                                  dense: true,
                                  title: Text('Rollo #${idx + 1}: ${roll['weight']} Kg'),
                                  subtitle: roll['color'].toString().isNotEmpty
                                      ? Text('Color: ${roll['color']} | Lote: ${roll['batch']}')
                                      : null,
                                  trailing: IconButton(
                                    icon: const Icon(Icons.delete, color: Colors.red, size: 18),
                                    onPressed: () {
                                      setModal(() {
                                        variableRolls.removeAt(idx);
                                      });
                                    },
                                  ),
                                ),
                              );
                            },
                          ),
                        ),
                        const SizedBox(height: 10),
                        Container(
                          width: double.infinity,
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(color: Colors.blue.shade50, borderRadius: BorderRadius.circular(8)),
                          child: Text(
                            'Resumen: ${variableRolls.length} Rollos  |  Total Peso: ${totalVariableWeight().toStringAsFixed(2)} Kg',
                            style: TextStyle(color: Colors.blue.shade900, fontWeight: FontWeight.bold),
                          ),
                        ),
                      ],
                    ] else ...[
                      Row(
                        children: [
                          Expanded(
                            child: TextField(
                              controller: qtyController,
                              keyboardType: const TextInputType.numberWithOptions(decimal: true),
                              decoration: InputDecoration(
                                labelText: 'Cantidad',
                                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                                prefixIcon: const Icon(Icons.calculate_outlined),
                              ),
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: TextField(
                              controller: weightController,
                              keyboardType: const TextInputType.numberWithOptions(decimal: true),
                              decoration: InputDecoration(
                                labelText: 'Peso Total (Kg)',
                                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                                prefixIcon: const Icon(Icons.scale_outlined),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ],
                    const SizedBox(height: 20),
                    SizedBox(
                      width: double.infinity,
                      height: 50,
                      child: ElevatedButton.icon(
                        onPressed: () {
                          final operator = operatorController.text.trim();
                          if (operator.isEmpty) {
                            ScaffoldMessenger.of(ctx).showSnackBar(
                              const SnackBar(content: Text('Por favor indique el nombre del operador')),
                            );
                            return;
                          }

                          double q = 0.0;
                          double w = 0.0;

                          if (selectedProduct!.isVariableQuantity) {
                            if (variableRolls.isEmpty) {
                              ScaffoldMessenger.of(ctx).showSnackBar(
                                const SnackBar(content: Text('Por favor cargue al menos un rollo')),
                              );
                              return;
                            }
                            q = variableRolls.length.toDouble();
                            w = totalVariableWeight();
                          } else {
                            q = double.tryParse(qtyController.text) ?? 0.0;
                            w = double.tryParse(weightController.text) ?? 0.0;
                            if (q <= 0 || w <= 0) {
                              ScaffoldMessenger.of(ctx).showSnackBar(
                                const SnackBar(content: Text('Indique cantidad y peso válidos')),
                              );
                              return;
                            }
                          }

                          setState(() {
                            _entries.add(ProductionEntry(
                              product: selectedProduct!,
                              quantity: q,
                              weight: w,
                              operatorName: operator,
                              productionDate: itemProductionDate,
                              metadata: variableRolls,
                            ));
                          });

                          // Save last used operator
                          SharedPreferences.getInstance().then((prefs) {
                            prefs.setString('last_operator_name', operator);
                          });

                          Navigator.pop(ctx);
                        },
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF1B263B),
                          foregroundColor: Colors.white,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        icon: const Icon(Icons.add_circle_outline),
                        label: const Text('AGREGAR'),
                      ),
                    ),
                  ],
                  const SizedBox(height: 8),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  Future<void> _submit() async {
    if (_entries.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Agregue al menos un producto terminado')),
      );
      return;
    }
    setState(() => _isSubmitting = true);
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');
      final dateStr = DateFormat('yyyy-MM-dd').format(_productionDate);

      final body = {
        'production_date': dateStr,
        'notes': _notesController.text.trim(),
        'details': _entries
            .map((e) => {
                  'product_id': e.product.id,
                  'quantity': e.quantity,
                  'weight': e.weight,
                  'operator_name': e.operatorName,
                  'production_date': DateFormat('yyyy-MM-dd').format(e.productionDate),
                  'metadata': e.metadata.isNotEmpty ? e.metadata : null,
                })
            .toList(),
      };

      final response = await http.post(
        Uri.parse('${widget.baseUrl}/api/bolsas/production'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: json.encode(body),
      ).timeout(const Duration(seconds: 20));

      final data = json.decode(response.body);
      if (response.statusCode == 200 && data['success'] == true) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Levantamiento registrado exitosamente'), backgroundColor: Colors.green),
          );
          Navigator.pop(context);
        }
      } else {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(data['message'] ?? 'Error al registrar'), backgroundColor: Colors.red),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error al enviar: $e'), backgroundColor: Colors.red),
        );
      }
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF4F6F9),
      appBar: AppBar(
        title: const Text('Registrar Levantamiento'),
        backgroundColor: const Color(0xFF1B263B),
        foregroundColor: Colors.white,
      ),
      body: _isLoadingProducts
          ? const Center(child: CircularProgressIndicator())
          : Column(
              children: [
                Expanded(
                  child: SingleChildScrollView(
                    padding: const EdgeInsets.all(15),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Date Picker Header
                        Card(
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                          child: ListTile(
                            leading: const Icon(Icons.calendar_month, color: Color(0xFF1B263B)),
                            title: const Text('Día de Producción', style: TextStyle(fontWeight: FontWeight.bold)),
                            subtitle: Text(DateFormat('dd / MM / yyyy').format(_productionDate)),
                            trailing: OutlinedButton(
                              onPressed: _selectDate,
                              child: const Text('CAMBIAR'),
                            ),
                          ),
                        ),
                        const SizedBox(height: 15),
                        if (_entries.isEmpty)
                          Padding(
                            padding: const EdgeInsets.symmetric(vertical: 60),
                            child: Center(
                              child: Column(
                                children: [
                                  Icon(Icons.assignment_turned_in_outlined, size: 60, color: Colors.grey.shade400),
                                  const SizedBox(height: 12),
                                  Text(
                                    'Sin productos cargados',
                                    style: TextStyle(color: Colors.grey.shade600, fontSize: 15),
                                  ),
                                  const SizedBox(height: 4),
                                  Text(
                                    'Toque "Agregar Bolsa" para comenzar el pesaje',
                                    style: TextStyle(color: Colors.grey.shade400, fontSize: 12),
                                  ),
                                ],
                              ),
                            ),
                          )
                        else
                          ListView.builder(
                            shrinkWrap: true,
                            physics: const NeverScrollableScrollPhysics(),
                            itemCount: _entries.length,
                            itemBuilder: (context, i) {
                              final e = _entries[i];
                              return Card(
                                margin: const EdgeInsets.only(bottom: 12),
                                elevation: 2,
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                                      decoration: const BoxDecoration(
                                        color: Color(0xFF1B263B),
                                        borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
                                      ),
                                      child: Row(
                                        children: [
                                          const Icon(Icons.shopping_bag_outlined, color: Colors.white, size: 18),
                                          const SizedBox(width: 8),
                                          Expanded(
                                            child: Text(
                                              e.product.name,
                                              style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
                                            ),
                                          ),
                                          IconButton(
                                            icon: const Icon(Icons.delete_outline, color: Colors.redAccent, size: 20),
                                            padding: EdgeInsets.zero,
                                            constraints: const BoxConstraints(),
                                            onPressed: () => setState(() => _entries.removeAt(i)),
                                          ),
                                        ],
                                      ),
                                    ),
                                    Padding(
                                      padding: const EdgeInsets.all(14),
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Row(
                                            children: [
                                              _infoBadge('Cantidad', '${e.quantity.toInt()} uds', Colors.blue),
                                              const SizedBox(width: 10),
                                              _infoBadge('Peso Total', '${e.weight.toStringAsFixed(2)} Kg', Colors.teal),
                                            ],
                                          ),
                                          const SizedBox(height: 8),
                                          Row(
                                            children: [
                                              _infoBadge('Operador', e.operatorName, Colors.deepPurple),
                                              const SizedBox(width: 10),
                                              _infoBadge('Fecha Prod.', DateFormat('dd/MM/yyyy').format(e.productionDate), Colors.orange),
                                            ],
                                          ),
                                          if (e.metadata.isNotEmpty) ...[
                                            const SizedBox(height: 12),
                                            const Text('Detalle de Rollos:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 11)),
                                            const SizedBox(height: 4),
                                            ...e.metadata.asMap().entries.map((rollEntry) {
                                              final idx = rollEntry.key;
                                              final roll = rollEntry.value;
                                              return Padding(
                                                padding: const EdgeInsets.only(left: 10, top: 2),
                                                child: Text(
                                                  '- Rollo #${idx + 1}: ${roll['weight']} Kg' +
                                                      (roll['color'].toString().isNotEmpty
                                                          ? ' (Color: ${roll['color']} | Lote: ${roll['batch']})'
                                                          : ''),
                                                  style: const TextStyle(fontSize: 12, color: Colors.black87),
                                                ),
                                              );
                                            }),
                                          ],
                                        ],
                                      ),
                                    ),
                                  ],
                                ),
                              );
                            },
                          ),
                        if (_entries.isNotEmpty) ...[
                          const SizedBox(height: 15),
                          TextField(
                            controller: _notesController,
                            decoration: InputDecoration(
                              labelText: 'Notas / Observaciones del Levantamiento',
                              prefixIcon: const Icon(Icons.notes),
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                            ),
                            maxLines: 2,
                          ),
                        ],
                        const SizedBox(height: 80),
                      ],
                    ),
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 15, vertical: 12),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    boxShadow: [
                      BoxShadow(color: Colors.black.withOpacity(0.06), blurRadius: 10, offset: const Offset(0, -3))
                    ],
                  ),
                  child: Row(
                    children: [
                      Expanded(
                        child: OutlinedButton.icon(
                          onPressed: _isSubmitting || _isLoadingProducts ? null : _showAddProductDialog,
                          icon: const Icon(Icons.add),
                          label: const Text('Agregar Bolsa'),
                          style: OutlinedButton.styleFrom(
                            foregroundColor: const Color(0xFF1B263B),
                            side: const BorderSide(color: Color(0xFF1B263B)),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                            padding: const EdgeInsets.symmetric(vertical: 14),
                          ),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: ElevatedButton.icon(
                          onPressed: (_isSubmitting || _entries.isEmpty) ? null : _submit,
                          icon: _isSubmitting
                              ? const SizedBox(
                                  width: 18,
                                  height: 18,
                                  child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                                )
                              : const Icon(Icons.cloud_upload_outlined),
                          label: Text(_isSubmitting ? 'Subiendo...' : 'SUBIR LOTE'),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.green.shade700,
                            foregroundColor: Colors.white,
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                            padding: const EdgeInsets.symmetric(vertical: 14),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
    );
  }

  Widget _infoBadge(String label, String value, Color color) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 6),
        decoration: BoxDecoration(
          color: color.withOpacity(0.1),
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: color.withOpacity(0.3)),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(label, style: TextStyle(fontSize: 9, color: color.withOpacity(0.8))),
            Text(
              value,
              style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: color),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ],
        ),
      ),
    );
  }
}

// --- HISTORIAL SCREEN ---
class ProductionHistoryScreen extends StatefulWidget {
  final String baseUrl;
  const ProductionHistoryScreen({super.key, required this.baseUrl});
  @override
  State<ProductionHistoryScreen> createState() => _ProductionHistoryScreenState();
}

class _ProductionHistoryScreenState extends State<ProductionHistoryScreen> {
  List<dynamic> _historyList = [];
  bool _isLoading = false;
  final _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _fetchHistory();
  }

  Future<void> _fetchHistory() async {
    setState(() => _isLoading = true);
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');
      final search = _searchController.text.trim();

      final uri = Uri.parse('${widget.baseUrl}/api/bolsas/production/history')
          .replace(queryParameters: search.isNotEmpty ? {'search': search} : null);

      final response = await http.get(
        uri,
        headers: {'Authorization': 'Bearer $token', 'Accept': 'application/json'},
      ).timeout(const Duration(seconds: 15));

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        setState(() {
          _historyList = data['data']['data'] ?? [];
        });
      }
    } catch (e) {
      debugPrint("History Fetch Err: $e");
    } finally {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF4F6F9),
      appBar: AppBar(
        title: const Text('Historial de Producción'),
        backgroundColor: const Color(0xFF1B263B),
        foregroundColor: Colors.white,
      ),
      body: Column(
        children: [
          // Filter Search Bar
          Padding(
            padding: const EdgeInsets.all(12),
            child: TextField(
              controller: _searchController,
              decoration: InputDecoration(
                hintText: 'Buscar por operador o producto...',
                prefixIcon: const Icon(Icons.search),
                suffixIcon: IconButton(
                  icon: const Icon(Icons.search_rounded),
                  onPressed: _fetchHistory,
                ),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                filled: true,
                fillColor: Colors.white,
                contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
              ),
              onSubmitted: (_) => _fetchHistory(),
            ),
          ),
          Expanded(
            child: _isLoading && _historyList.isEmpty
                ? const Center(child: CircularProgressIndicator())
                : RefreshIndicator(
                    onRefresh: _fetchHistory,
                    child: _historyList.isEmpty
                        ? Center(
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(Icons.inventory_2_outlined, size: 60, color: Colors.grey.shade300),
                                const SizedBox(height: 10),
                                const Text('No hay registros de producción en el historial',
                                    style: TextStyle(color: Colors.grey)),
                              ],
                            ),
                          )
                        : ListView.builder(
                            padding: const EdgeInsets.all(10),
                            itemCount: _historyList.length,
                            itemBuilder: (context, index) {
                              final p = _historyList[index];
                              final details = p['details'] as List? ?? [];
                              final dateStr = DateFormat('dd / MM / yyyy')
                                  .format(DateTime.parse(p['production_date']));
                              final createdStr = DateFormat('dd/MM/yyyy HH:mm')
                                  .format(DateTime.parse(p['created_at']));

                              // Aggregate totals for header display
                              final totalQty = details.fold(0.0, (sum, item) => sum + double.parse(item['quantity'].toString()));
                              final totalWeight = details.fold(0.0, (sum, item) => sum + double.parse(item['weight'].toString()));

                              return Card(
                                elevation: 3,
                                margin: const EdgeInsets.only(bottom: 12),
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                                child: ExpansionTile(
                                  leading: CircleAvatar(
                                    backgroundColor: const Color(0xFF1B263B).withOpacity(0.1),
                                    child: const Icon(Icons.layers, color: Color(0xFF1B263B)),
                                  ),
                                  title: Text('Lote #${p['id']} - $dateStr',
                                      style: const TextStyle(fontWeight: FontWeight.bold)),
                                  subtitle: Text(
                                    'Subido por: ${p['user']?['name'] ?? 'Desconocido'} - $createdStr\n${totalQty.toInt()} uds | ${totalWeight.toStringAsFixed(2)} Kg',
                                    style: const TextStyle(fontSize: 11, color: Colors.grey),
                                  ),
                                  children: [
                                    Padding(
                                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          if (p['note'] != null && p['note'].toString().isNotEmpty) ...[
                                            Text(
                                              'Notas: ${p['note']}',
                                              style: const TextStyle(
                                                  fontStyle: FontStyle.italic, color: Colors.black87),
                                            ),
                                            const Divider(),
                                          ],
                                          const Text(
                                            'Detalle de Productos:',
                                            style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                                          ),
                                          const SizedBox(height: 6),
                                          ...(() {
                                            final Map<String, List<dynamic>> groupedDetails = {};
                                            for (var d in details) {
                                              final dateStr = d['production_date'] ?? p['production_date'] ?? 'Sin Fecha';
                                              if (!groupedDetails.containsKey(dateStr)) {
                                                groupedDetails[dateStr] = [];
                                              }
                                              groupedDetails[dateStr]!.add(d);
                                            }
                                            
                                            return groupedDetails.entries.expand((entry) {
                                              final dateStr = entry.key;
                                              final items = entry.value;
                                              
                                              String formattedGroupHeader;
                                              try {
                                                final parsedDate = DateTime.parse(dateStr);
                                                final weekdaysEs = ['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];
                                                final weekdayName = weekdaysEs[parsedDate.weekday - 1];
                                                final dateFormatted = DateFormat('dd-MM-yyyy').format(parsedDate);
                                                formattedGroupHeader = '$weekdayName $dateFormatted';
                                              } catch (_) {
                                                formattedGroupHeader = dateStr;
                                              }

                                              return [
                                                Padding(
                                                  padding: const EdgeInsets.only(top: 10.0, bottom: 4.0),
                                                  child: Text(
                                                    formattedGroupHeader.toUpperCase(),
                                                    style: const TextStyle(
                                                      fontWeight: FontWeight.bold,
                                                      fontSize: 11,
                                                      color: Color(0xFF1B263B),
                                                    ),
                                                  ),
                                                ),
                                                ...items.map((d) {
                                                  final meta = d['metadata'] as List? ?? [];
                                                  return Column(
                                                    crossAxisAlignment: CrossAxisAlignment.start,
                                                    children: [
                                                      ListTile(
                                                        dense: true,
                                                        contentPadding: EdgeInsets.zero,
                                                        title: Text(
                                                          d['product']['name'] ?? 'Bolsa',
                                                          style: const TextStyle(fontWeight: FontWeight.bold),
                                                        ),
                                                        subtitle: Text(
                                                          'Operario: ${d['operator_name']} | Peso: ${d['weight']} Kg',
                                                          style: const TextStyle(fontSize: 11),
                                                        ),
                                                        trailing: Text(
                                                          '${double.parse(d['quantity'].toString()).toInt()} uds',
                                                          style: const TextStyle(fontWeight: FontWeight.bold),
                                                        ),
                                                      ),
                                                      if (meta.isNotEmpty) ...[
                                                        Padding(
                                                          padding: const EdgeInsets.only(left: 15, bottom: 8),
                                                          child: Column(
                                                            crossAxisAlignment: CrossAxisAlignment.start,
                                                            children: meta.asMap().entries.map((rollEntry) {
                                                              final idx = rollEntry.key;
                                                              final roll = rollEntry.value;
                                                              return Text(
                                                                '• Rollo #${idx + 1}: ${roll['weight']} Kg' +
                                                                    (roll['color'].toString().isNotEmpty
                                                                        ? ' [Color: ${roll['color']} | Lote: ${roll['batch']}]'
                                                                        : ''),
                                                                style: const TextStyle(
                                                                    fontSize: 10, color: Colors.black54),
                                                              );
                                                            }).toList(),
                                                          ),
                                                        ),
                                                      ],
                                                    ],
                                                  );
                                                }),
                                                const Divider(height: 1, color: Colors.black12),
                                              ];
                                            });
                                          })(),
                                        ],
                                      ),
                                    )
                                  ],
                                ),
                              );
                            },
                          ),
                  ),
          ),
        ],
      ),
    );
  }
}
