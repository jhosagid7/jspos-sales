import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:package_info_plus/package_info_plus.dart';
import 'dart:math';

void main() {
  runApp(const SopladosApp());
}

class SopladosApp extends StatelessWidget {
  const SopladosApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Soplados',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF2C3E50), primary: const Color(0xFF2C3E50)),
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
  void initState() { super.initState(); _init(); }
  _init() async { 
    final prefs = await SharedPreferences.getInstance(); 
    final pkg = await PackageInfo.fromPlatform();
    
    String token = prefs.getString('device_token') ?? '';
    if (token.isEmpty) {
      token = 'SOPLADOS-${DateTime.now().millisecondsSinceEpoch}-${_generateRandomString(4)}';
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
        headers: {
          'Accept': 'application/json',
          'X-Device-Token': _deviceToken
        },
        body: {
          'email': _emailController.text, 
          'password': _passwordController.text, 
          'device_name': 'Mobile (Soplados)',
          'app_type': 'soplados'
        },
      ).timeout(const Duration(seconds: 15));

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        final prefs = await SharedPreferences.getInstance();
        await prefs.setInt('user_id', data['user']['id'] ?? 0);
        await prefs.setString('token', data['token'] ?? '');
        await prefs.setString('user_name', data['user']['name'] ?? 'Operador Soplados');
        await prefs.setBool('is_manager', data['user']['is_soplados_manager'] ?? false);
        await prefs.setString('last_email', _emailController.text);
        
        if (mounted) Navigator.pushReplacement(context, MaterialPageRoute(builder: (context) => DashboardScreen(baseUrl: _baseUrl)));
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
        decoration: const BoxDecoration(gradient: LinearGradient(begin: Alignment.topCenter, end: Alignment.bottomCenter, colors: [Color(0xFF2C3E50), Color(0xFF34495E)])),
        child: Column(
          children: [
            const SizedBox(height: 60),
            Row(mainAxisAlignment: MainAxisAlignment.end, children: [IconButton(onPressed: _showSettings, icon: const Icon(Icons.settings, color: Colors.white70))]),
            const Icon(Icons.factory_rounded, size: 100, color: Colors.amberAccent),
            const Text('JSPOS Soplados', style: TextStyle(color: Colors.white, fontSize: 36, fontWeight: FontWeight.bold)),
            const Text('Panel de Operadores de Planta', style: TextStyle(color: Colors.white70, fontSize: 16)),
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
                      style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF2C3E50), foregroundColor: Colors.white, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10))),
                      child: _isLoading ? const CircularProgressIndicator(color: Colors.white) : const Text('ENTRAR', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                    ),
                  ),
                  const SizedBox(height: 10),
                  Text('Servidor: $_baseUrl  •  Versión Soplados: $_appVersion', style: const TextStyle(fontSize: 10, color: Colors.grey)),
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
  bool _isLoading = false;
  bool _isManager = false;
  
  Map<String, dynamic>? _currentShift;
  int _pendingDispatches = 0;
  int _pendingReturns = 0;
  int _pendingReceipts = 0;
  int _pendingInventories = 0;

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
      _userName = prefs.getString('user_name') ?? "Operador Soplados"; 
      _isManager = prefs.getBool('is_manager') ?? false;
    }); 
    _refreshDashboard();
  }

  Future<void> _refreshDashboard() async {
    await _fetchCurrentShift();
    await _fetchTransferCounts();
    await _fetchPendingInventories();
  }

  Future<void> _fetchPendingInventories() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');
      final response = await http.get(Uri.parse('$_baseUrl/api/soplados/inventory/pending'), headers: {
        'Authorization': 'Bearer $token', 
        'Accept': 'application/json',
      }).timeout(const Duration(seconds: 10));
      
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        final List list = data['inventories'] ?? [];
        setState(() {
          _pendingInventories = list.length;
        });
      }
    } catch (e) { debugPrint("Pending Inv Err: $e"); }
  }

  Future<void> _fetchTransferCounts() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');
      final response = await http.get(Uri.parse('$_baseUrl/api/soplados/transfers/counts'), headers: {
        'Authorization': 'Bearer $token', 
        'Accept': 'application/json',
      }).timeout(const Duration(seconds: 10));
      
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        setState(() {
          _pendingDispatches = data['counts']['dispatches'] ?? 0;
          _pendingReturns = data['counts']['returns'] ?? 0;
          _pendingReceipts = data['counts']['receipts'] ?? 0;
        });
      }
    } catch (e) { debugPrint("Counts Err: $e"); }
  }

  Future<void> _fetchCurrentShift() async {
    setState(() => _isLoading = true);
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');
      final response = await http.get(Uri.parse('$_baseUrl/api/soplados/shifts/current'), headers: {
        'Authorization': 'Bearer $token', 
        'Accept': 'application/json',
      }).timeout(const Duration(seconds: 10));
      
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        setState(() {
          _currentShift = data['shift'];
        });
      }
    } catch (e) { debugPrint("Shift Err: $e"); }
    finally { setState(() => _isLoading = false); }
  }

  Future<void> _openShift(String type) async {
    setState(() => _isLoading = true);
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');
      final response = await http.post(
        Uri.parse('$_baseUrl/api/soplados/shifts/open'), 
        headers: {
          'Authorization': 'Bearer $token', 
          'Accept': 'application/json',
        },
        body: {'type': type}
      ).timeout(const Duration(seconds: 10));
      
      if (response.statusCode == 200 || response.statusCode == 201) {
        final data = json.decode(response.body);
        setState(() {
          _currentShift = data['shift'];
        });
        if(mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Turno abierto exitosamente'), backgroundColor: Colors.green));
      } else {
        if(mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: ${response.body}'), backgroundColor: Colors.red));
      }
    } catch (e) { debugPrint("Shift Err: $e"); }
    finally { setState(() => _isLoading = false); }
  }

  Future<void> _closeShift() async {
    if (_currentShift == null) return;
    setState(() => _isLoading = true);
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');
      final response = await http.post(
        Uri.parse('$_baseUrl/api/soplados/shifts/close'), 
        headers: {
          'Authorization': 'Bearer $token', 
          'Accept': 'application/json',
        },
        body: {'shift_id': _currentShift!['id'].toString()}
      ).timeout(const Duration(seconds: 10));
      
      if (response.statusCode == 200) {
        setState(() { _currentShift = null; });
        if(mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Turno cerrado exitosamente'), backgroundColor: Colors.green));
      } else {
        if(mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: ${response.body}'), backgroundColor: Colors.red));
      }
    } catch (e) { debugPrint("Shift Err: $e"); }
    finally { setState(() => _isLoading = false); }
  }

  void _showOpenShiftDialog() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Abrir Turno'),
        content: const Text('Seleccione el tipo de turno a aperturar:'),
        actions: [
          TextButton(onPressed: () { Navigator.pop(context); _openShift('diurno'); }, child: const Text('DIURNO')),
          TextButton(onPressed: () { Navigator.pop(context); _openShift('nocturno'); }, child: const Text('NOCTURNO')),
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('CANCELAR')),
        ],
      ),
    );
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
      backgroundColor: const Color(0xFFF8F9FA),
      appBar: AppBar(
        backgroundColor: const Color(0xFF2C3E50),
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.white),
        title: const Text('Soplados Dashboard', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w900, fontSize: 20)),
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: _refreshDashboard),
        ],
      ),
      drawer: Drawer(
        child: Column(
          children: [
            UserAccountsDrawerHeader(
              decoration: const BoxDecoration(color: Color(0xFF2C3E50)),
              accountName: Text(_userName, style: const TextStyle(fontWeight: FontWeight.bold)),
              accountEmail: const Text("Operador de Planta"),
              currentAccountPicture: const CircleAvatar(backgroundColor: Colors.white, child: Icon(Icons.factory_rounded, color: Color(0xFF2C3E50), size: 40)),
            ),
            ListTile(leading: const Icon(Icons.logout, color: Colors.red), title: const Text('Cerrar Sesión'), onTap: _logout),
            const Spacer(),
            const Divider(height: 1),
            Padding(
              padding: const EdgeInsets.only(bottom: 25, top: 15),
              child: Text('v$_appVersion', style: const TextStyle(color: Colors.grey, fontSize: 11)),
            ),
          ],
        ),
      ),
      body: RefreshIndicator(
        onRefresh: _refreshDashboard,
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              if (_isLoading) const LinearProgressIndicator(),
              Container(
                width: double.infinity,
                margin: const EdgeInsets.all(15),
                padding: const EdgeInsets.symmetric(horizontal: 25, vertical: 30),
                decoration: BoxDecoration(
                  gradient: LinearGradient(colors: [const Color(0xFF34495E), const Color(0xFF2C3E50).withOpacity(0.9)], begin: Alignment.topLeft, end: Alignment.bottomRight),
                  borderRadius: BorderRadius.circular(20),
                  boxShadow: [BoxShadow(color: const Color(0xFF2C3E50).withOpacity(0.4), blurRadius: 20, offset: const Offset(0, 10))]
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                     Text('Operador(a),', style: TextStyle(color: Colors.white.withOpacity(0.9), fontSize: 16)),
                     const SizedBox(height: 5),
                     Text(_userName, style: const TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.w900, height: 1.1), maxLines: 2, overflow: TextOverflow.ellipsis),
                     const SizedBox(height: 25),
                     if (_currentShift != null && _currentShift!['stats'] != null) ...[
                        Row(
                          children: [
                            _statCircle('Rend.', '${_currentShift!['stats']['yield']}%', Colors.greenAccent),
                            const SizedBox(width: 15),
                            _statCircle('Buenos', '${_currentShift!['stats']['good_production']}', Colors.blueAccent),
                            const SizedBox(width: 15),
                            _statCircle('Merma', '${_currentShift!['stats']['damaged_production']}', Colors.redAccent),
                          ],
                        ),
                        const SizedBox(height: 20),
                     ],
                     _currentShift == null 
                      ? Container(
                          padding: const EdgeInsets.all(15),
                          decoration: BoxDecoration(color: Colors.white.withOpacity(0.1), borderRadius: BorderRadius.circular(10)),
                          child: Row(
                            children: [
                              const Icon(Icons.warning_amber_rounded, color: Colors.amberAccent),
                              const SizedBox(width: 10),
                              const Expanded(child: Text('NO HAY UN TURNO ACTIVO', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold))),
                              ElevatedButton(
                                onPressed: _showOpenShiftDialog, 
                                style: ElevatedButton.styleFrom(backgroundColor: Colors.white, foregroundColor: const Color(0xFF2C3E50)),
                                child: const Text('ABRIR')
                              )
                            ],
                          ),
                        )
                      : Container(
                          padding: const EdgeInsets.all(15),
                          decoration: BoxDecoration(color: Colors.green.withOpacity(0.2), borderRadius: BorderRadius.circular(10), border: Border.all(color: Colors.greenAccent)),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  const Icon(Icons.check_circle_outline, color: Colors.greenAccent),
                                  const SizedBox(width: 10),
                                  Expanded(child: Text('TURNO ABIERTO: ${_currentShift!['type'].toString().toUpperCase()}', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold))),
                                ],
                              ),
                              const SizedBox(height: 10),
                              Text('Abierto a las: ${_currentShift!['opened_at']}', style: const TextStyle(color: Colors.white70, fontSize: 12)),
                              const SizedBox(height: 10),
                              SizedBox(
                                width: double.infinity,
                                child: ElevatedButton(
                                  onPressed: () {
                                    showDialog(
                                      context: context,
                                      builder: (context) => AlertDialog(
                                        title: const Text('Cerrar Turno'),
                                        content: const Text('¿Está seguro de cerrar el turno actual?'),
                                        actions: [
                                          TextButton(onPressed: () => Navigator.pop(context), child: const Text('CANCELAR')),
                                          TextButton(onPressed: () { Navigator.pop(context); _closeShift(); }, child: const Text('CERRAR TURNO', style: TextStyle(color: Colors.red))),
                                        ],
                                      ),
                                    );
                                  }, 
                                  style: ElevatedButton.styleFrom(backgroundColor: Colors.red.shade400, foregroundColor: Colors.white),
                                  child: const Text('CERRAR TURNO')
                                ),
                              )
                            ],
                          ),
                        )
                  ],
                ),
              ),

              const Padding(
                padding: EdgeInsets.symmetric(horizontal: 25, vertical: 10),
                child: Text('Acciones', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: Color(0xFF1B263B))),
              ),

              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 15),
                child: GridView.count(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  crossAxisCount: 2,
                  crossAxisSpacing: 15,
                  mainAxisSpacing: 15,
                  childAspectRatio: 1.1,
                  children: [
                    _menuCard('Registrar\nProducción', Icons.precision_manufacturing_rounded, Colors.blueAccent, () {
                      if (_currentShift == null) {
                        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Debe abrir un turno primero')));
                        return;
                      }
                      Navigator.push(context, MaterialPageRoute(builder: (context) => ProductionScreen(baseUrl: _baseUrl, shiftId: _currentShift!['id'])));
                    }, 0),
                    _menuCard('Traspasos\n(Salidas)', Icons.local_shipping_rounded, Colors.indigoAccent, () {
                      Navigator.push(context, MaterialPageRoute(builder: (context) => TransfersScreen(baseUrl: _baseUrl, type: 'dispatches')));
                    }, _pendingDispatches),
                    _menuCard('Retornos\n(Entradas)', Icons.assignment_return_rounded, Colors.orange, () {
                      Navigator.push(context, MaterialPageRoute(builder: (context) => TransfersScreen(baseUrl: _baseUrl, type: 'returns')));
                    }, _pendingReturns),
                    _menuCard('Inventario\nde Planta', Icons.inventory_rounded, Colors.teal, () {
                      Navigator.push(context, MaterialPageRoute(builder: (context) => InventoryScreen(baseUrl: _baseUrl))).then((_) => _refreshDashboard());
                    }, _pendingReceipts),
                    if (_isManager)
                      _menuCard('Hacer\nInventario', Icons.assignment_turned_in_rounded, Colors.cyan.shade700, () {
                        Navigator.push(context, MaterialPageRoute(builder: (context) => SupervisorInventoryFormScreen(baseUrl: _baseUrl))).then((_) => _refreshDashboard());
                      }, 0),
                    _menuCard('Confirmar\nInventario', Icons.rate_review_rounded, Colors.amber.shade800, () {
                      Navigator.push(context, MaterialPageRoute(builder: (context) => OperatorConformityScreen(baseUrl: _baseUrl))).then((_) => _refreshDashboard());
                    }, _pendingInventories),
                    _menuCard('Historial\nInventarios', Icons.summarize_rounded, Colors.blueGrey, () {
                      Navigator.push(context, MaterialPageRoute(builder: (context) => InventoryHistoryScreen(baseUrl: _baseUrl)));
                    }, 0),
                    _menuCard('Historial de\nTurnos', Icons.history_toggle_off_rounded, Colors.deepPurple, () {
                      Navigator.push(context, MaterialPageRoute(builder: (context) => ShiftHistoryScreen(baseUrl: _baseUrl)));
                    }, 0),
                    _menuCard('Historial de\nProducción', Icons.history_rounded, Colors.purple, () {
                      Navigator.push(context, MaterialPageRoute(builder: (context) => ProductionHistoryScreen(baseUrl: _baseUrl)));
                    }, 0),
                  ],
                ),
              ),
              const SizedBox(height: 50),
            ],
          ),
        ),
      ),
    );
  }

  Widget _menuCard(String title, IconData icon, Color color, VoidCallback onTap, int badgeCount) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(20),
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(20),
          boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 10, offset: const Offset(0, 5))]
        ),
        child: Stack(
          alignment: Alignment.center,
          children: [
            Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Container(
                  padding: const EdgeInsets.all(15),
                  decoration: BoxDecoration(color: color.withOpacity(0.12), shape: BoxShape.circle),
                  child: Icon(icon, color: color, size: 35),
                ),
                const SizedBox(height: 15),
                Text(title, textAlign: TextAlign.center, style: TextStyle(fontSize: 13, fontWeight: FontWeight.w900, color: Colors.grey.shade800, height: 1.2))
              ],
            ),
            if (badgeCount > 0)
              Positioned(
                top: 15, right: 15,
                child: Container(
                  padding: const EdgeInsets.all(6),
                  decoration: const BoxDecoration(color: Colors.red, shape: BoxShape.circle),
                  constraints: const BoxConstraints(minWidth: 22, minHeight: 22),
                  child: Text('$badgeCount', style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold), textAlign: TextAlign.center),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _statCircle(String label, String value, Color color) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12),
        decoration: BoxDecoration(color: Colors.white.withOpacity(0.1), borderRadius: BorderRadius.circular(15), border: Border.all(color: color.withOpacity(0.3))),
        child: Column(
          children: [
            Text(value, style: TextStyle(color: color, fontSize: 18, fontWeight: FontWeight.bold)),
            Text(label, style: TextStyle(color: Colors.white.withOpacity(0.7), fontSize: 10)),
          ],
        ),
      ),
    );
  }
}

// --- MODELS FOR PRODUCTION ---
class ProductSimple {
  final int id;
  final String name;
  final String sku;
  ProductSimple({required this.id, required this.name, required this.sku});
  factory ProductSimple.fromJson(Map<String, dynamic> json) => ProductSimple(
    id: int.parse(json['id'].toString()),
    name: json['name'],
    sku: json['sku'] ?? '',
  );
}

class FormulaIngredient {
  final int ingredientId;
  final String ingredientName;
  final double quantityPerUnit;
  double totalQuantity;
  FormulaIngredient({
    required this.ingredientId,
    required this.ingredientName,
    required this.quantityPerUnit,
    required this.totalQuantity,
  });
}

class OutputEntry {
  final ProductSimple product;
  double quantity;
  String quality;
  List<FormulaIngredient> ingredients;
  OutputEntry({
    required this.product,
    required this.quantity,
    required this.quality,
    required this.ingredients,
  });
}


// --- PRODUCTION SCREEN ---
class ProductionScreen extends StatefulWidget {
  final String baseUrl;
  final int shiftId;
  const ProductionScreen({super.key, required this.baseUrl, required this.shiftId});
  @override
  State<ProductionScreen> createState() => _ProductionScreenState();
}

class _ProductionScreenState extends State<ProductionScreen> {
  List<ProductSimple> _availableProducts = [];
  final List<OutputEntry> _entries = [];
  bool _isLoadingProducts = false;
  bool _isSubmitting = false;
  final _notesController = TextEditingController();
  final _searchController = TextEditingController();

  @override
  void initState() { super.initState(); _fetchProducts(); }

  Future<void> _fetchProducts() async {
    setState(() => _isLoadingProducts = true);
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');
      final response = await http.get(
        Uri.parse('${widget.baseUrl}/api/soplados/products'),
        headers: {'Authorization': 'Bearer $token', 'Accept': 'application/json'},
      ).timeout(const Duration(seconds: 15));
      if (response.statusCode == 200) {
        final List data = json.decode(response.body);
        setState(() => _availableProducts = data.map((e) => ProductSimple.fromJson(e)).toList());
      }
    } catch (e) { debugPrint('Fetch products err: $e'); }
    finally { setState(() => _isLoadingProducts = false); }
  }

  Future<List<FormulaIngredient>?> _fetchFormula(int productId, double quantity) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');
      final response = await http.get(
        Uri.parse('${widget.baseUrl}/api/soplados/products/$productId/formula'),
        headers: {'Authorization': 'Bearer $token', 'Accept': 'application/json'},
      ).timeout(const Duration(seconds: 10));
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        final List formulaList = data['formula'];
        return formulaList.map((f) => FormulaIngredient(
          ingredientId: int.parse(f['ingredient_id'].toString()),
          ingredientName: f['ingredient_name'],
          quantityPerUnit: double.parse(f['quantity_per_unit'].toString()),
          totalQuantity: double.parse(f['quantity_per_unit'].toString()) * quantity,
        )).toList();
      } else {
        final data = json.decode(response.body);
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(data['message'] ?? 'Sin fórmula configurada'), backgroundColor: Colors.orange));
        return null;
      }
    } catch (e) { debugPrint('Formula err: $e'); return null; }
  }

  void _showAddProductDialog() {
    ProductSimple? selectedProduct;
    final qtyController = TextEditingController();
    final bultosController = TextEditingController();
    final unidadesController = TextEditingController();
    String quality = '1st';
    String searchQuery = '';
    bool isLoadingFormula = false;
    double equivalency = 1.0;
    bool isBulkProduct = false;
    List<FormulaIngredient>? fetchedFormulaIngredients;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setModal) {
          final filtered = _availableProducts
              .where((p) => p.name.toLowerCase().contains(searchQuery.toLowerCase()))
              .toList();

          double calculateDecimal() {
            if (isBulkProduct) {
              double b = double.tryParse(bultosController.text) ?? 0.0;
              double u = double.tryParse(unidadesController.text) ?? 0.0;
              return b + (u / equivalency);
            } else {
              return double.tryParse(qtyController.text) ?? 0.0;
            }
          }

          int calculateTotalUnits() {
            if (isBulkProduct) {
              double b = double.tryParse(bultosController.text) ?? 0.0;
              double u = double.tryParse(unidadesController.text) ?? 0.0;
              return (b * equivalency + u).round();
            } else {
              double q = double.tryParse(qtyController.text) ?? 0.0;
              return q.round();
            }
          }

          return Container(
            decoration: const BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
            ),
            padding: EdgeInsets.only(
              bottom: MediaQuery.of(ctx).viewInsets.bottom + 20,
              left: 20, right: 20, top: 20,
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Center(child: Container(width: 40, height: 4,
                  decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(2)))),
                const SizedBox(height: 16),
                const Text('Agregar Producto Terminado',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF2C3E50))),
                const SizedBox(height: 16),
                if (selectedProduct == null) ...[
                  // Search
                  TextField(
                    decoration: InputDecoration(
                      hintText: 'Buscar producto...',
                      prefixIcon: const Icon(Icons.search),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                    ),
                    onChanged: (v) => setModal(() => searchQuery = v),
                  ),
                  const SizedBox(height: 12),
                  Container(
                    constraints: const BoxConstraints(maxHeight: 200),
                    decoration: BoxDecoration(border: Border.all(color: Colors.grey.shade200), borderRadius: BorderRadius.circular(12)),
                    child: filtered.isEmpty
                      ? const Padding(padding: EdgeInsets.all(20),
                          child: Center(child: Text('Sin productos con fórmula', style: TextStyle(color: Colors.grey))))
                      : ListView.separated(
                          shrinkWrap: true,
                          itemCount: filtered.length,
                          separatorBuilder: (_, __) => Divider(height: 1, color: Colors.grey.shade100),
                          itemBuilder: (_, i) => ListTile(
                            dense: true,
                            leading: Container(
                              padding: const EdgeInsets.all(6),
                              decoration: BoxDecoration(color: const Color(0xFF2C3E50).withOpacity(0.1), borderRadius: BorderRadius.circular(8)),
                              child: const Icon(Icons.inventory_2_outlined, color: Color(0xFF2C3E50), size: 18),
                            ),
                            title: Text(filtered[i].name, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
                            subtitle: filtered[i].sku.isNotEmpty ? Text(filtered[i].sku, style: const TextStyle(fontSize: 11)) : null,
                            onTap: () async {
                              setModal(() {
                                selectedProduct = filtered[i];
                                isLoadingFormula = true;
                                searchQuery = '';
                              });
                              
                              final formula = await _fetchFormula(selectedProduct!.id, 1.0);
                              
                              setModal(() {
                                isLoadingFormula = false;
                                if (formula != null && formula.isNotEmpty) {
                                  fetchedFormulaIngredients = formula;
                                  double firstQty = formula.first.quantityPerUnit;
                                  if (firstQty > 1.0) {
                                    isBulkProduct = true;
                                    equivalency = firstQty;
                                  } else {
                                    isBulkProduct = false;
                                    equivalency = 1.0;
                                  }
                                } else {
                                  isBulkProduct = false;
                                  equivalency = 1.0;
                                }
                              });
                            },
                          ),
                        ),
                  ),
                ] else ...[
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                    decoration: BoxDecoration(
                      color: const Color(0xFF2C3E50).withOpacity(0.08), borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: const Color(0xFF2C3E50).withOpacity(0.3)),
                    ),
                    child: Row(children: [
                      const Icon(Icons.check_circle, color: Color(0xFF2C3E50), size: 20),
                      const SizedBox(width: 8),
                      Expanded(child: Text(selectedProduct!.name, style: const TextStyle(fontWeight: FontWeight.bold))),
                      GestureDetector(
                        onTap: () => setModal(() {
                          selectedProduct = null;
                          fetchedFormulaIngredients = null;
                          isBulkProduct = false;
                          qtyController.clear();
                          bultosController.clear();
                          unidadesController.clear();
                        }),
                        child: const Icon(Icons.close, size: 18, color: Colors.grey)),
                    ]),
                  ),
                  const SizedBox(height: 14),
                  if (isLoadingFormula)
                    const Center(child: Padding(
                      padding: EdgeInsets.all(20),
                      child: CircularProgressIndicator(),
                    ))
                  else if (fetchedFormulaIngredients == null)
                    const Padding(
                      padding: EdgeInsets.all(10),
                      child: Text('Este producto no tiene una receta configurada.', style: TextStyle(color: Colors.red, fontWeight: FontWeight.bold)),
                    )
                  else ...[
                    if (isBulkProduct) ...[
                      Row(
                        children: [
                          Expanded(
                            child: TextField(
                              controller: bultosController,
                              keyboardType: TextInputType.number,
                              decoration: InputDecoration(
                                labelText: 'Bultos',
                                hintText: '0',
                                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                              ),
                              onChanged: (_) => setModal(() {}),
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: TextField(
                              controller: unidadesController,
                              keyboardType: TextInputType.number,
                              decoration: InputDecoration(
                                labelText: 'Unidades Sueltas',
                                hintText: '0',
                                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                              ),
                              onChanged: (_) => setModal(() {}),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 10),
                      Text(
                        'Equivalencia: 1 bulto = ${equivalency.toInt()} unidades.',
                        style: TextStyle(fontSize: 12, color: Colors.grey.shade600, fontStyle: FontStyle.italic),
                      ),
                      const SizedBox(height: 10),
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: Colors.blue.shade50,
                          borderRadius: BorderRadius.circular(10),
                          border: Border.all(color: Colors.blue.shade200),
                        ),
                        child: Text(
                          'Total a registrar: ${calculateDecimal().toStringAsFixed(4)} bultos (${calculateTotalUnits()} unidades)',
                          style: TextStyle(color: Colors.blue.shade800, fontWeight: FontWeight.bold, fontSize: 13),
                        ),
                      ),
                    ] else ...[
                      TextField(
                        controller: qtyController,
                        keyboardType: const TextInputType.numberWithOptions(decimal: true),
                        decoration: InputDecoration(
                          labelText: 'Cantidad (Unidades)',
                          prefixIcon: const Icon(Icons.production_quantity_limits),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        onChanged: (_) => setModal(() {}),
                      ),
                    ],
                    const SizedBox(height: 14),
                    const Text('Calidad / Estado', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                    const SizedBox(height: 8),
                    Row(children: [
                      _qualityChip(setModal, '1st', '1ra Cal.', quality, (v) => quality = v),
                      const SizedBox(width: 8),
                      _qualityChip(setModal, '2nd', '2da Cal.', quality, (v) => quality = v),
                      const SizedBox(width: 8),
                      _qualityChip(setModal, 'damaged', 'Merma', quality, (v) => quality = v),
                    ]),
                    const SizedBox(height: 20),
                    SizedBox(
                      width: double.infinity, height: 50,
                      child: ElevatedButton.icon(
                        onPressed: (isLoadingFormula || fetchedFormulaIngredients == null || calculateDecimal() <= 0)
                          ? null
                          : () {
                              final qty = calculateDecimal();
                              final finalIngredients = fetchedFormulaIngredients!.map((ing) => FormulaIngredient(
                                ingredientId: ing.ingredientId,
                                ingredientName: ing.ingredientName,
                                quantityPerUnit: ing.quantityPerUnit,
                                totalQuantity: ing.quantityPerUnit * qty,
                              )).toList();

                              setState(() => _entries.add(OutputEntry(
                                product: selectedProduct!, quantity: qty, quality: quality, ingredients: finalIngredients)));

                              Navigator.pop(ctx);
                            },
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF2C3E50), foregroundColor: Colors.white,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        icon: const Icon(Icons.add_circle_outline),
                        label: const Text('AGREGAR'),
                      ),
                    ),
                  ],
                ],
                const SizedBox(height: 8),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _qualityChip(StateSetter setModal, String val, String label, String current, Function(String) onChanged) {
    final selected = current == val;
    return Expanded(
      child: GestureDetector(
        onTap: () => setModal(() => onChanged(val)),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 10),
          decoration: BoxDecoration(
            color: selected ? const Color(0xFF2C3E50) : Colors.grey.shade100,
            borderRadius: BorderRadius.circular(10),
            border: Border.all(color: selected ? const Color(0xFF2C3E50) : Colors.grey.shade300),
          ),
          child: Text(label, textAlign: TextAlign.center,
            style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700,
              color: selected ? Colors.white : Colors.grey.shade700)),
        ),
      ),
    );
  }

  Future<void> _submit() async {
    if (_entries.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Agregue al menos un producto terminado')));
      return;
    }
    setState(() => _isSubmitting = true);
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');
      final body = {
        'shift_id': widget.shiftId,
        'notes': _notesController.text,
        'outputs': _entries.map((e) => {'product_id': e.product.id, 'quantity': e.quantity, 'quality': e.quality}).toList(),
      };
      final response = await http.post(
        Uri.parse('${widget.baseUrl}/api/soplados/production'),
        headers: {'Authorization': 'Bearer $token', 'Accept': 'application/json', 'Content-Type': 'application/json'},
        body: json.encode(body),
      ).timeout(const Duration(seconds: 20));
      final data = json.decode(response.body);
      if (response.statusCode == 200 && data['success'] == true) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Produccion registrada exitosamente'), backgroundColor: Colors.green));
          Navigator.pop(context);
        }
      } else {
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(data['message'] ?? 'Error al registrar'), backgroundColor: Colors.red));
      }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red));
    } finally { setState(() => _isSubmitting = false); }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FA),
      appBar: AppBar(
        title: const Text('Registro de Produccion'),
        backgroundColor: const Color(0xFF2C3E50),
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
                      if (_availableProducts.isEmpty)
                        Container(
                          width: double.infinity,
                          padding: const EdgeInsets.all(20),
                          decoration: BoxDecoration(
                            color: Colors.orange.shade50, borderRadius: BorderRadius.circular(12),
                            border: Border.all(color: Colors.orange.shade200),
                          ),
                          child: const Column(children: [
                            Icon(Icons.warning_amber_rounded, color: Colors.orange, size: 40),
                            SizedBox(height: 10),
                            Text('Fábrica sin Recetas Configuradas',
                              textAlign: TextAlign.center,
                              style: TextStyle(fontWeight: FontWeight.bold, color: Colors.orange)),
                            SizedBox(height: 6),
                            Text('Solicite al Administrador configurar las fórmulas en el panel web (Fábrica Soplados > Configuración de Recetas)',
                              textAlign: TextAlign.center,
                              style: TextStyle(fontSize: 12, color: Colors.grey)),
                          ]),
                        )
                      else if (_entries.isEmpty)
                        Padding(
                          padding: const EdgeInsets.symmetric(vertical: 50),
                          child: Center(child: Column(children: [
                            Icon(Icons.precision_manufacturing_outlined, size: 60, color: Colors.grey.shade400),
                            const SizedBox(height: 12),
                            Text('Sin productos registrados', style: TextStyle(color: Colors.grey.shade600, fontSize: 15)),
                            const SizedBox(height: 4),
                            Text('Toca "Agregar Producto" para comenzar',
                              style: TextStyle(color: Colors.grey.shade400, fontSize: 12)),
                          ])),
                        )
                      else
                        ...(_entries.asMap().entries.map((entry) {
                          final i = entry.key;
                          final e = entry.value;
                          final qualityLabel = e.quality == '1st' ? '1ra Calidad' : e.quality == '2nd' ? '2da Calidad' : 'Merma';
                          final qualityColor = e.quality == '1st' ? Colors.green : e.quality == '2nd' ? Colors.orange : Colors.red;

                          final hasEquivalency = e.ingredients.isNotEmpty && e.ingredients.first.quantityPerUnit > 1.0;
                          final eq = hasEquivalency ? e.ingredients.first.quantityPerUnit : 1.0;
                          final displayQty = hasEquivalency
                              ? '${e.quantity.toStringAsFixed(4)} bultos (${(e.quantity * eq).round()} uds)'
                              : '${e.quantity % 1 == 0 ? e.quantity.toInt() : e.quantity} uds';

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
                                    color: Color(0xFF2C3E50),
                                    borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
                                  ),
                                  child: Row(children: [
                                    const Icon(Icons.inventory_2, color: Colors.white, size: 18),
                                    const SizedBox(width: 8),
                                    Expanded(child: Text(e.product.name,
                                      style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold))),
                                    IconButton(
                                      icon: const Icon(Icons.delete_outline, color: Colors.redAccent, size: 20),
                                      padding: EdgeInsets.zero, constraints: const BoxConstraints(),
                                      onPressed: () => setState(() => _entries.removeAt(i)),
                                    ),
                                  ]),
                                ),
                                Padding(
                                  padding: const EdgeInsets.all(14),
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Row(children: [
                                        _infoBadge('Cantidad', displayQty, Colors.blue),
                                        const SizedBox(width: 10),
                                        _infoBadge('Calidad', qualityLabel, qualityColor),
                                      ]),
                                      const SizedBox(height: 12),
                                      Row(children: [
                                        const Icon(Icons.auto_awesome, size: 14, color: Colors.grey),
                                        const SizedBox(width: 4),
                                        Text('Insumos calculados automaticamente',
                                          style: TextStyle(fontSize: 11, color: Colors.grey.shade600, fontWeight: FontWeight.w600)),
                                      ]),
                                      const SizedBox(height: 6),
                                      ...e.ingredients.map((ing) => Padding(
                                        padding: const EdgeInsets.only(top: 4),
                                        child: Row(children: [
                                          const Icon(Icons.chevron_right, size: 16, color: Colors.grey),
                                          Expanded(child: Text(ing.ingredientName, style: const TextStyle(fontSize: 13))),
                                          Text(
                                            '${ing.totalQuantity % 1 == 0 ? ing.totalQuantity.toInt() : ing.totalQuantity} uds',
                                            style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold)),
                                        ]),
                                      )),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                          );
                        })),
                      if (_entries.isNotEmpty) ...[
                        const SizedBox(height: 16),
                        TextField(
                          controller: _notesController,
                          decoration: InputDecoration(
                            labelText: 'Notas / Observaciones (opcional)',
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
                  boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.06), blurRadius: 10, offset: const Offset(0, -3))],
                ),
                child: Row(children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: _isSubmitting || _availableProducts.isEmpty ? null : _showAddProductDialog,
                      icon: const Icon(Icons.add),
                      label: const Text('Agregar Producto'),
                      style: OutlinedButton.styleFrom(
                        foregroundColor: const Color(0xFF2C3E50),
                        side: const BorderSide(color: Color(0xFF2C3E50)),
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
                        ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                        : const Icon(Icons.check_circle_outline),
                      label: Text(_isSubmitting ? 'Registrando...' : 'REGISTRAR'),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.green.shade700, foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        padding: const EdgeInsets.symmetric(vertical: 14),
                      ),
                    ),
                  ),
                ]),
              ),
            ],
          ),
    );
  }

  Widget _infoBadge(String label, String value, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1), borderRadius: BorderRadius.circular(8),
        border: Border.all(color: color.withOpacity(0.3)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: TextStyle(fontSize: 10, color: color.withOpacity(0.8))),
          Text(value, style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: color)),
        ],
      ),
    );
  }
}

// --- TRANSFERS SCREEN ---
class TransfersScreen extends StatefulWidget {
  final String baseUrl;
  final String type; // 'dispatches' or 'returns'
  const TransfersScreen({super.key, required this.baseUrl, required this.type});
  @override
  State<TransfersScreen> createState() => _TransfersScreenState();
}

class _TransfersScreenState extends State<TransfersScreen> {
  List<dynamic> _transfers = [];
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _fetchData();
  }

  Future<void> _fetchData() async {
    setState(() => _isLoading = true);
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');
      final endpoint = widget.type == 'dispatches' 
        ? '/api/soplados/transfers/pending' 
        : '/api/soplados/transfers/returns/pending';

      final response = await http.get(Uri.parse('${widget.baseUrl}$endpoint'), headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
      }).timeout(const Duration(seconds: 15));
      
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        setState(() { _transfers = data['transfers']; });
      }
    } catch (e) { debugPrint("Fetch Trans Err: $e"); }
    finally { setState(() => _isLoading = false); }
  }

  Future<void> _processAction(int id) async {
    setState(() => _isLoading = true);
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');
      final endpoint = widget.type == 'dispatches'
        ? '/api/soplados/transfers/$id/dispatch'
        : '/api/soplados/transfers/$id/returns/receive';

      final response = await http.post(
        Uri.parse('${widget.baseUrl}$endpoint'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        }
      ).timeout(const Duration(seconds: 15));
      
      if (response.statusCode == 200) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(
            content: Text(widget.type == 'dispatches' ? 'Mercancía despachada' : 'Devolución recibida'), 
            backgroundColor: Colors.green
          ));
        }
        _fetchData();
      } else {
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: ${response.body}'), backgroundColor: Colors.red));
      }
    } catch (e) { debugPrint("Process Trans Err: $e"); }
    finally { setState(() => _isLoading = false); }
  }

  @override
  Widget build(BuildContext context) {
    final isDispatch = widget.type == 'dispatches';
    final themeColor = isDispatch ? Colors.indigoAccent : Colors.orange;

    return Scaffold(
      appBar: AppBar(
        title: Text(isDispatch ? 'Despachos Pendientes' : 'Retornos por Recibir'),
        backgroundColor: themeColor,
        foregroundColor: Colors.white,
      ),
      body: _isLoading && _transfers.isEmpty 
        ? const Center(child: CircularProgressIndicator())
        : RefreshIndicator(
            onRefresh: _fetchData,
            child: _transfers.isEmpty 
              ? Center(child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(isDispatch ? Icons.local_shipping_outlined : Icons.assignment_return_outlined, size: 60, color: Colors.grey.shade300),
                    const SizedBox(height: 10),
                    Text(isDispatch ? 'No hay despachos pendientes' : 'No hay retornos pendientes', style: const TextStyle(color: Colors.grey)),
                  ],
                ))
              : ListView.builder(
                  padding: const EdgeInsets.all(10),
                  itemCount: _transfers.length,
                  itemBuilder: (context, index) {
                    final t = _transfers[index];
                    return Card(
                      elevation: 3,
                      margin: const EdgeInsets.only(bottom: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      child: ExpansionTile(
                        leading: CircleAvatar(backgroundColor: themeColor.withOpacity(0.1), child: Icon(isDispatch ? Icons.outbox : Icons.inbox, color: themeColor)),
                        title: Text('Traspaso #${t['id']}', style: const TextStyle(fontWeight: FontWeight.bold)),
                        subtitle: Text(isDispatch ? 'Destino: ${t['dest_warehouse_name'] ?? 'General'}' : 'Devolución de: ${t['dest_warehouse_name'] ?? 'General'}'),
                        children: [
                          Padding(
                            padding: const EdgeInsets.all(15),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                if (t['rejection_reason'] != null) ...[
                                  Text('Motivo Rechazo:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Colors.red.shade700)),
                                  Text(t['rejection_reason'], style: const TextStyle(fontSize: 12)),
                                  const Divider(),
                                ],
                                const Text('Productos:', style: TextStyle(fontWeight: FontWeight.bold)),
                                ...(t['details'] as List).map((d) {
                                  final qty = isDispatch ? d['quantity'] : d['rejected_quantity'];
                                  return ListTile(
                                    dense: true,
                                    contentPadding: EdgeInsets.zero,
                                    title: Text(d['product_name'] ?? 'Producto'),
                                    trailing: Text('$qty uds', style: const TextStyle(fontWeight: FontWeight.bold)),
                                  );
                                }).toList(),
                                const SizedBox(height: 15),
                                SizedBox(
                                  width: double.infinity,
                                  height: 45,
                                  child: ElevatedButton.icon(
                                    onPressed: () => _processAction(t['id']),
                                    icon: Icon(isDispatch ? Icons.local_shipping : Icons.check_circle, color: Colors.white),
                                    label: Text(isDispatch ? 'CONFIRMAR DESPACHO' : 'CONFIRMAR RECEPCIÓN', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
                                    style: ElevatedButton.styleFrom(backgroundColor: themeColor, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8))),
                                  ),
                                )
                              ],
                            ),
                          )
                        ],
                      ),
                    );
                  },
                ),
          ),
    );
  }
}


// --- INVENTORY & RECEIPTS SCREEN ---
class InventoryScreen extends StatefulWidget {
  final String baseUrl;
  const InventoryScreen({super.key, required this.baseUrl});
  @override
  State<InventoryScreen> createState() => _InventoryScreenState();
}

class _InventoryScreenState extends State<InventoryScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  List<dynamic> _inventory = [];
  List<dynamic> _receipts = [];
  bool _isLoading = false;
  String _warehouseName = "Planta";

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _fetchInventory();
    _fetchReceipts();
  }

  Future<void> _fetchInventory() async {
    setState(() => _isLoading = true);
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');
      final response = await http.get(Uri.parse('${widget.baseUrl}/api/soplados/inventory'), headers: {
        'Authorization': 'Bearer $token', 'Accept': 'application/json',
      }).timeout(const Duration(seconds: 15));
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        setState(() { 
          _inventory = data['inventory']; 
          _warehouseName = data['warehouse'] ?? "Planta";
        });
      } else {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(
            content: Text('Error al cargar inventario: ${response.statusCode} - ${response.body}'),
            backgroundColor: Colors.red,
          ));
        }
      }
    } catch (e) { 
      debugPrint("Inv Err: $e");
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
          content: Text('Error de conexión al cargar inventario: $e'),
          backgroundColor: Colors.red,
        ));
      }
    } finally { setState(() => _isLoading = false); }
  }

  Future<void> _fetchReceipts() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');
      final response = await http.get(Uri.parse('${widget.baseUrl}/api/soplados/receipts/pending'), headers: {
        'Authorization': 'Bearer $token', 'Accept': 'application/json',
      }).timeout(const Duration(seconds: 15));
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        setState(() { _receipts = data['transfers']; });
      } else {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(
            content: Text('Error al cargar recibos: ${response.statusCode}'),
            backgroundColor: Colors.red,
          ));
        }
      }
    } catch (e) { 
      debugPrint("Receipts Err: $e");
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
          content: Text('Error de conexión al cargar recibos: $e'),
          backgroundColor: Colors.red,
        ));
      }
    }
  }

  // Modal/Partial state
  Map<int, double> _receivedQtys = {};
  final Map<int, TextEditingController> _controllers = {};
  final TextEditingController _reasonController = TextEditingController();

  Future<void> _receiveTransfer(int id, List details) async {
    setState(() => _isLoading = true);
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');

      // Build items array for API
      final items = details.map((d) {
        final detailId = int.parse(d['id'].toString());
        return {
          'id': detailId,
          'received': _receivedQtys[detailId] ?? double.parse(d['quantity'].toString())
        };
      }).toList();

      final response = await http.post(
        Uri.parse('${widget.baseUrl}/api/soplados/receipts/$id/receive'),
        headers: {
          'Authorization': 'Bearer $token', 
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: json.encode({
          'items': items,
          'rejection_reason': _reasonController.text
        })
      ).timeout(const Duration(seconds: 15));

      if (response.statusCode == 200) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Recepción procesada'), backgroundColor: Colors.green));
        }
        _reasonController.clear();
        _receivedQtys.clear();
        _controllers.clear();
        _fetchInventory();
        _fetchReceipts();
      } else {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: ${response.body}'), backgroundColor: Colors.red));
        }
      }
    } catch (e) { debugPrint("Rec Err: $e"); }
    finally { setState(() => _isLoading = false); }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FA),
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Inventario de Planta', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.white)),
            Text(_warehouseName, style: const TextStyle(fontSize: 12, color: Colors.white70)),
          ],
        ),
        backgroundColor: Colors.teal,
        foregroundColor: Colors.white,
        bottom: TabBar(
          controller: _tabController,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white60,
          indicatorColor: Colors.amberAccent,
          tabs: const [
            Tab(icon: Icon(Icons.inventory_2_outlined), text: 'STOCK ACTUAL'),
            Tab(icon: Icon(Icons.input_rounded), text: 'RECIBIR INSUMOS'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildStockTab(),
          _buildReceiptsTab(),
        ],
      ),
    );
  }

  Widget _buildStockTab() {
    if (_isLoading && _inventory.isEmpty) return const Center(child: CircularProgressIndicator());
    final terminados = _inventory.where((i) => i['type'] == 'Producto Terminado').toList();
    final insumos = _inventory.where((i) => i['type'] != 'Producto Terminado').toList();

    return RefreshIndicator(
      onRefresh: _fetchInventory,
      child: ListView(
        padding: const EdgeInsets.all(15),
        children: [
          _sectionHeader('INSUMOS Y MATERIA PRIMA', Icons.category_outlined),
          if (insumos.isEmpty) const Center(child: Padding(padding: EdgeInsets.all(20), child: Text('No hay insumos registrados', style: TextStyle(color: Colors.grey))))
          else ...insumos.map((i) => _inventoryCard(i, Colors.orange)),
          const SizedBox(height: 20),
          _sectionHeader('PRODUCTOS TERMINADOS', Icons.check_circle_outline),
          if (terminados.isEmpty) const Center(child: Padding(padding: EdgeInsets.all(20), child: Text('No hay productos en stock', style: TextStyle(color: Colors.grey))))
          else ...terminados.map((i) => _inventoryCard(i, Colors.blue)),
        ],
      ),
    );
  }

  Widget _sectionHeader(String title, IconData icon) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10, top: 5),
      child: Row(children: [
        Icon(icon, size: 16, color: Colors.grey),
        const SizedBox(width: 8),
        Text(title, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.grey, letterSpacing: 1)),
      ]),
    );
  }

  Widget _inventoryCard(Map i, Color color) {
    return Card(
      elevation: 0,
      margin: const EdgeInsets.only(bottom: 8),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12), side: BorderSide(color: Colors.grey.shade200)),
      child: ListTile(
        leading: CircleAvatar(backgroundColor: color.withOpacity(0.1), child: Icon(Icons.inventory_2, color: color, size: 20)),
        title: Text(i['name'], style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
        subtitle: Text(i['sku'] ?? '', style: const TextStyle(fontSize: 11)),
        trailing: Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
          decoration: BoxDecoration(color: color.withOpacity(0.1), borderRadius: BorderRadius.circular(20)),
          child: Text('${i['stock']} uds', style: TextStyle(color: color, fontWeight: FontWeight.bold, fontSize: 14)),
        ),
      ),
    );
  }

  Widget _buildReceiptsTab() {
    if (_isLoading && _receipts.isEmpty) return const Center(child: CircularProgressIndicator());
    return RefreshIndicator(
      onRefresh: _fetchReceipts,
      child: Column(
        children: [
          if (_receipts.isNotEmpty)
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(12),
              color: Colors.amber.shade100,
              child: Row(
                children: [
                  const Icon(Icons.info_outline, color: Colors.orange),
                  const SizedBox(width: 10),
                  Expanded(child: Text('Tienes ${_receipts.length} carga(s) de insumos pendientes por recibir.', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13))),
                ],
              ),
            ),
          Expanded(
            child: _receipts.isEmpty
              ? Center(child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [Icon(Icons.local_shipping_outlined, size: 60, color: Colors.grey.shade300), const Text('No hay insumos pendientes de recibir', style: TextStyle(color: Colors.grey))]))
              : ListView.builder(
                  padding: const EdgeInsets.all(15),
                  itemCount: _receipts.length,
                  itemBuilder: (context, index) {
                    final t = _receipts[index];
                    final List details = t['details'];

                    return Card(
                      elevation: 2,
                      margin: const EdgeInsets.only(bottom: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
                      child: ExpansionTile(
                        leading: const CircleAvatar(backgroundColor: Colors.tealAccent, child: Icon(Icons.input, color: Colors.teal)),
                        title: Text('Carga #${t['id']}', style: const TextStyle(fontWeight: FontWeight.bold)),
                        subtitle: Text('De: ${t['origin_name']}'),
                        children: [
                          Padding(
                            padding: const EdgeInsets.all(15),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text('Verifique las cantidades recibidas:', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.grey)),
                                const SizedBox(height: 10),
                                ...details.map((d) {
                                  final detailId = int.parse(d['id'].toString());
                                  final requested = double.parse(d['quantity'].toString());
                                  
                                  // Initialize controllers and values if not set
                                  if (!_controllers.containsKey(detailId)) {
                                    _controllers[detailId] = TextEditingController(text: requested.toString());
                                    _receivedQtys[detailId] = requested;
                                  }

                                  return Padding(
                                    padding: const EdgeInsets.only(bottom: 10),
                                    child: Row(
                                      children: [
                                        Expanded(child: Text(d['product_name'], style: const TextStyle(fontSize: 13))),
                                        const SizedBox(width: 10),
                                        SizedBox(
                                          width: 100,
                                          child: TextFormField(
                                            controller: _controllers[detailId],
                                            keyboardType: const TextInputType.numberWithOptions(decimal: true),
                                            textAlign: TextAlign.center,
                                            style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.teal),
                                            decoration: InputDecoration(
                                              contentPadding: const EdgeInsets.symmetric(horizontal: 8, vertical: 8),
                                              isDense: true,
                                              suffixText: 'uds',
                                              suffixStyle: const TextStyle(fontSize: 10),
                                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                                              labelText: 'Recibido',
                                              labelStyle: const TextStyle(fontSize: 10)
                                            ),
                                            onChanged: (val) {
                                              setState(() {
                                                _receivedQtys[detailId] = double.tryParse(val) ?? requested;
                                              });
                                            },
                                          ),
                                        ),
                                      ],
                                    ),
                                  );
                                }).toList(),
                                const SizedBox(height: 10),
                                const Divider(),
                                TextFormField(
                                  controller: _reasonController,
                                  decoration: const InputDecoration(
                                    labelText: 'Observación / Motivo (Si hay faltante)',
                                    labelStyle: TextStyle(fontSize: 12),
                                    border: OutlineInputBorder(),
                                    isDense: true,
                                  ),
                                  maxLines: 2,
                                ),
                                const SizedBox(height: 15),
                                SizedBox(
                                  width: double.infinity, 
                                  height: 48, 
                                  child: ElevatedButton.icon(
                                    onPressed: () => _receiveTransfer(t['id'], details), 
                                    icon: const Icon(Icons.check_circle, color: Colors.white), 
                                    label: const Text('CONFIRMAR Y CARGAR A PLANTA', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)), 
                                    style: ElevatedButton.styleFrom(backgroundColor: Colors.teal, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)))
                                  )
                                ),
                              ],
                            ),
                          )
                        ],
                      ),
                    );
                  },
                ),
          ),
        ],
      ),
    );
  }
}

class ProductionHistoryScreen extends StatefulWidget {
  final String baseUrl;
  const ProductionHistoryScreen({super.key, required this.baseUrl});
  @override
  State<ProductionHistoryScreen> createState() => _ProductionHistoryScreenState();
}

class _ProductionHistoryScreenState extends State<ProductionHistoryScreen> {
  List _logs = [];
  bool _isLoading = true;
  DateTime _dateFrom = DateTime.now().subtract(const Duration(days: 7));
  DateTime _dateTo = DateTime.now();

  @override
  void initState() {
    super.initState();
    _fetchHistory();
  }

  Future<void> _fetchHistory() async {
    setState(() => _isLoading = true);
    try {
      final token = await _Storage.getToken();
      final from = "${_dateFrom.year}-${_dateFrom.month.toString().padLeft(2, '0')}-${_dateFrom.day.toString().padLeft(2, '0')}";
      final to = "${_dateTo.year}-${_dateTo.month.toString().padLeft(2, '0')}-${_dateTo.day.toString().padLeft(2, '0')}";
      
      final response = await http.get(
        Uri.parse('${widget.baseUrl}/api/soplados/production/history?date_from=$from&date_to=$to'),
        headers: {'Authorization': 'Bearer $token', 'Accept': 'application/json'},
      ).timeout(const Duration(seconds: 15));

      final data = json.decode(response.body);
      if (response.statusCode == 200 && data['success'] == true) {
        setState(() => _logs = data['data']['data']);
      }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e')));
    } finally { setState(() => _isLoading = false); }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FA),
      appBar: AppBar(
        title: const Text('Historial de Producción'),
        backgroundColor: const Color(0xFF2C3E50),
        foregroundColor: Colors.white,
      ),
      body: Column(
        children: [
          Container(
            padding: const EdgeInsets.all(15),
            color: Colors.white,
            child: Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    icon: const Icon(Icons.calendar_today, size: 16),
                    label: Text("${_dateFrom.day}/${_dateFrom.month}/${_dateFrom.year}"),
                    onPressed: () async {
                      final picked = await showDatePicker(context: context, initialDate: _dateFrom, firstDate: DateTime(2023), lastDate: DateTime.now());
                      if (picked != null) { setState(() => _dateFrom = picked); _fetchHistory(); }
                    },
                  ),
                ),
                const Padding(padding: EdgeInsets.symmetric(horizontal: 8), child: Text('-')),
                Expanded(
                  child: OutlinedButton.icon(
                    icon: const Icon(Icons.calendar_today, size: 16),
                    label: Text("${_dateTo.day}/${_dateTo.month}/${_dateTo.year}"),
                    onPressed: () async {
                      final picked = await showDatePicker(context: context, initialDate: _dateTo, firstDate: DateTime(2023), lastDate: DateTime.now());
                      if (picked != null) { setState(() => _dateTo = picked); _fetchHistory(); }
                    },
                  ),
                ),
              ],
            ),
          ),
          Expanded(
            child: _isLoading 
              ? const Center(child: CircularProgressIndicator())
              : _logs.isEmpty
                ? const Center(child: Text('No hay registros en este periodo'))
                : ListView.builder(
                    padding: const EdgeInsets.all(15),
                    itemCount: _logs.length,
                    itemBuilder: (context, index) {
                      final log = _logs[index];
                      final stats = log['stats'];
                      final yieldVal = (stats['yield'] as num).toDouble();
                      
                      return Card(
                        margin: const EdgeInsets.only(bottom: 15),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
                        child: ExpansionTile(
                          leading: CircleAvatar(
                            backgroundColor: yieldVal >= 95 ? Colors.green.shade100 : Colors.red.shade100,
                            child: Text("${yieldVal.toInt()}%", style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: yieldVal >= 95 ? Colors.green.shade800 : Colors.red.shade800)),
                          ),
                          title: Text("#${log['id']} - ${log['shift'] != null ? log['shift']['type'].toString().toUpperCase() : 'N/A'}", style: const TextStyle(fontWeight: FontWeight.bold)),
                          subtitle: Text("Por: ${log['user']['name']} | ${log['created_at'].toString().split('T')[0]}"),
                          children: [
                            Padding(
                              padding: const EdgeInsets.all(15),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    mainAxisAlignment: MainAxisAlignment.spaceAround,
                                    children: [
                                      _miniStat('Buenos', stats['good'].toString(), Colors.green),
                                      _miniStat('Merma', stats['damaged'].toString(), Colors.red),
                                      _miniStat('Insumos', stats['materials'].toString(), Colors.blue),
                                    ],
                                  ),
                                  const Divider(height: 30),
                                  const Text('PRODUCTOS RESULTANTES:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Colors.grey)),
                                  ...(log['outputs'] as List).map((o) => Padding(
                                    padding: const EdgeInsets.only(top: 8),
                                    child: Row(
                                      children: [
                                        Expanded(child: Text(o['product']['name'] ?? 'Desconocido')),
                                        Text("${o['quantity']} ", style: const TextStyle(fontWeight: FontWeight.bold)),
                                        Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                          decoration: BoxDecoration(
                                            color: o['quality'] == '1st' ? Colors.green : o['quality'] == '2nd' ? Colors.orange : Colors.red,
                                            borderRadius: BorderRadius.circular(4)
                                          ),
                                          child: Text(o['quality'] == '1st' ? '1RA' : o['quality'] == '2nd' ? '2DA' : 'DAÑ', style: const TextStyle(color: Colors.white, fontSize: 10)),
                                        )
                                      ],
                                    ),
                                  )),
                                  if (log['notes'] != null && log['notes'].toString().isNotEmpty) ...[
                                    const SizedBox(height: 15),
                                    const Text('NOTAS:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Colors.grey)),
                                    Text(log['notes'], style: const TextStyle(fontStyle: FontStyle.italic)),
                                  ]
                                ],
                              ),
                            )
                          ],
                        ),
                      );
                    },
                  ),
          ),
        ],
      ),
    );
  }

  Widget _miniStat(String label, String value, Color color) {
    return Column(
      children: [
        Text(value, style: TextStyle(color: color, fontWeight: FontWeight.bold, fontSize: 18)),
        Text(label, style: const TextStyle(fontSize: 10, color: Colors.grey)),
      ],
    );
  }
}

class ShiftHistoryScreen extends StatefulWidget {
  final String baseUrl;
  const ShiftHistoryScreen({super.key, required this.baseUrl});
  @override
  State<ShiftHistoryScreen> createState() => _ShiftHistoryScreenState();
}

class _ShiftHistoryScreenState extends State<ShiftHistoryScreen> {
  List<dynamic> _shifts = [];
  bool _isLoading = false;

  @override
  void initState() { super.initState(); _fetchData(); }

  Future<void> _fetchData() async {
    setState(() => _isLoading = true);
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');
      final response = await http.get(Uri.parse('${widget.baseUrl}/api/soplados/shifts/history'), headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
      }).timeout(const Duration(seconds: 15));
      
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        setState(() => _shifts = data['data']['data']);
      }
    } catch (e) { debugPrint("Shift History Err: $e"); }
    finally { setState(() => _isLoading = false); }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Historial de Turnos'), backgroundColor: const Color(0xFF2C3E50), foregroundColor: Colors.white),
      body: _isLoading 
        ? const Center(child: CircularProgressIndicator())
        : _shifts.isEmpty
          ? const Center(child: Text('No hay turnos registrados'))
          : ListView.builder(
              padding: const EdgeInsets.all(15),
              itemCount: _shifts.length,
              itemBuilder: (context, i) {
                final s = _shifts[i];
                final stats = s['stats'];
                final date = s['start_time'].toString().split(' ')[0];
                final time = s['start_time'].toString().split(' ')[1].substring(0, 5);
                final status = s['status'] == 'open' ? 'ABIERTO' : 'CERRADO';
                final operator = s['users'] != null && s['users'].isNotEmpty ? s['users'][0]['name'] : 'N/A';
                
                return Card(
                  margin: const EdgeInsets.only(bottom: 15),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
                  child: Padding(
                    padding: const EdgeInsets.all(15),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text('Turno #${s['id']} - ${s['type'].toString().toUpperCase()}', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                              decoration: BoxDecoration(color: s['status'] == 'open' ? Colors.green : Colors.grey, borderRadius: BorderRadius.circular(5)),
                              child: Text(status, style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold)),
                            ),
                          ],
                        ),
                        const SizedBox(height: 5),
                        Text('Operador: $operator', style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13, color: Color(0xFF2C3E50))),
                        Text('Fecha: $date  •  Hora: $time', style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
                        const Divider(height: 25),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceAround,
                          children: [
                            _miniStat('Rend.', '${stats['yield']}%', stats['yield'] >= 95 ? Colors.green : Colors.orange),
                            _miniStat('Buenos', '${stats['good']}', Colors.blue),
                            _miniStat('Merma', '${stats['damaged']}', Colors.red),
                          ],
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),
    );
  }

  Widget _miniStat(String label, String value, Color color) {
    return Column(
      children: [
        Text(value, style: TextStyle(color: color, fontWeight: FontWeight.bold, fontSize: 16)),
        Text(label, style: const TextStyle(fontSize: 10, color: Colors.grey)),
      ],
    );
  }
}

class _Storage {
  static const String _tokenKey = 'token';
  static Future<void> saveToken(String token) async {
    final SharedPreferences prefs = await SharedPreferences.getInstance();
    await prefs.setString(_tokenKey, token);
  }
  static Future<String?> getToken() async {
    final SharedPreferences prefs = await SharedPreferences.getInstance();
    return prefs.getString(_tokenKey);
  }
}

// --- SUPERVISOR INVENTORY FORM SCREEN ---
class SupervisorInventoryFormScreen extends StatefulWidget {
  final String baseUrl;
  const SupervisorInventoryFormScreen({super.key, required this.baseUrl});

  @override
  State<SupervisorInventoryFormScreen> createState() => _SupervisorInventoryFormScreenState();
}

class _SupervisorInventoryFormScreenState extends State<SupervisorInventoryFormScreen> {
  List<dynamic> _products = [];
  bool _isLoading = true;
  bool _isSubmitting = false;
  final _notesController = TextEditingController();
  final Map<int, TextEditingController> _counted1raControllers = {};
  final Map<int, TextEditingController> _counted2daControllers = {};
  final Map<int, TextEditingController> _countedMermaControllers = {};

  @override
  void initState() {
    super.initState();
    _fetchProducts();
  }

  Future<void> _fetchProducts() async {
    setState(() => _isLoading = true);
    try {
      final token = await _Storage.getToken();
      final response = await http.get(
        Uri.parse('${widget.baseUrl}/api/soplados/inventory/products'),
        headers: {'Authorization': 'Bearer $token', 'Accept': 'application/json'},
      ).timeout(const Duration(seconds: 15));

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        setState(() {
          _products = data['products'];
          for (var p in _products) {
            int id = p['id'];
            _counted1raControllers[id] = TextEditingController(text: p['system_stock_primera'].toString());
            if (p['production_target_id'] != null) {
              _counted2daControllers[id] = TextEditingController(text: (p['system_stock_segunda'] ?? 0).toString());
            }
            _countedMermaControllers[id] = TextEditingController(text: '0');
          }
        });
      }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e')));
    } finally {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _submitCount() async {
    setState(() => _isSubmitting = true);
    try {
      final token = await _Storage.getToken();
      final List<Map<String, dynamic>> submitProducts = [];

      for (var p in _products) {
        int id = p['id'];
        double c1 = double.tryParse(_counted1raControllers[id]?.text ?? '') ?? 0.0;
        double? c2 = p['production_target_id'] != null 
            ? (double.tryParse(_counted2daControllers[id]?.text ?? '') ?? 0.0) 
            : null;
        double? cm = p['type'] == 'finished_product' 
            ? (double.tryParse(_countedMermaControllers[id]?.text ?? '') ?? 0.0) 
            : null;

        submitProducts.add({
          'id': id,
          'type': p['type'],
          'counted_primera': c1,
          'counted_segunda': c2,
          'counted_merma': cm,
        });
      }

      final body = {
        'notes': _notesController.text,
        'products': submitProducts,
      };

      final response = await http.post(
        Uri.parse('${widget.baseUrl}/api/soplados/inventory'),
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
          ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Inventario enviado al operador exitosamente'), backgroundColor: Colors.green));
          Navigator.pop(context);
        }
      } else {
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(data['message'] ?? 'Error al guardar'), backgroundColor: Colors.red));
      }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red));
    } finally {
      setState(() => _isSubmitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FA),
      appBar: AppBar(
        title: const Text('Conteo Físico (Supervisor)'),
        backgroundColor: Colors.cyan.shade700,
        foregroundColor: Colors.white,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : Column(
              children: [
                Expanded(
                  child: ListView.builder(
                    padding: const EdgeInsets.all(15),
                    itemCount: _products.length,
                    itemBuilder: (context, index) {
                      final p = _products[index];
                      int id = p['id'];
                      bool isFinished = p['type'] == 'finished_product';
                      
                      return Card(
                        elevation: 2,
                        margin: const EdgeInsets.only(bottom: 15),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
                        child: Padding(
                          padding: const EdgeInsets.all(15),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  CircleAvatar(
                                    backgroundColor: isFinished ? Colors.blue.shade50 : Colors.orange.shade50,
                                    child: Icon(isFinished ? Icons.local_drink : Icons.category, color: isFinished ? Colors.blue : Colors.orange),
                                  ),
                                  const SizedBox(width: 12),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(p['name'], style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                                        Text(isFinished ? 'Botellón' : 'Insumo', style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
                                      ],
                                    ),
                                  ),
                                ],
                              ),
                              const Divider(height: 25),
                              
                              // First Quality / Raw Material Row
                              _buildCountInputRow(
                                label: isFinished ? '1ra Calidad' : 'Cantidad Insumo',
                                systemStock: p['system_stock_primera'],
                                controller: _counted1raControllers[id]!,
                              ),
                              
                              // Second Quality Row (if applicable)
                              if (isFinished && p['production_target_id'] != null) ...[
                                const SizedBox(height: 12),
                                _buildCountInputRow(
                                  label: '2da Calidad',
                                  systemStock: p['system_stock_segunda'] ?? 0,
                                  controller: _counted2daControllers[id]!,
                                ),
                              ],
                              
                              // Merma Row (if finished product)
                              if (isFinished) ...[
                                const SizedBox(height: 12),
                                Row(
                                  children: [
                                    const Expanded(
                                      child: Text('Merma/Dañado (Físico)', style: TextStyle(fontWeight: FontWeight.w600)),
                                    ),
                                    SizedBox(
                                      width: 100,
                                      child: TextFormField(
                                        controller: _countedMermaControllers[id],
                                        keyboardType: TextInputType.number,
                                        textAlign: TextAlign.center,
                                        decoration: InputDecoration(
                                          isDense: true,
                                          contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                              ],
                            ],
                          ),
                        ),
                      );
                    },
                  ),
                ),
                Container(
                  padding: const EdgeInsets.all(15),
                  color: Colors.white,
                  child: Column(
                    children: [
                      TextField(
                        controller: _notesController,
                        decoration: InputDecoration(
                          hintText: 'Notas / Observaciones del supervisor...',
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                          isDense: true,
                        ),
                        maxLines: 2,
                      ),
                      const SizedBox(height: 12),
                      SizedBox(
                        width: double.infinity,
                        height: 50,
                        child: ElevatedButton.icon(
                          onPressed: _isSubmitting ? null : _submitCount,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.cyan.shade700,
                            foregroundColor: Colors.white,
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                          ),
                          icon: _isSubmitting 
                              ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                              : const Icon(Icons.send_rounded),
                          label: Text(_isSubmitting ? 'ENVIANDO...' : 'ENVIAR PARA CONFORMIDAD'),
                        ),
                      )
                    ],
                  ),
                ),
              ],
            ),
    );
  }

  Widget _buildCountInputRow({required String label, required dynamic systemStock, required TextEditingController controller}) {
    double sysVal = (systemStock as num).toDouble();
    return StatefulBuilder(
      builder: (context, setStateRow) {
        double currentVal = double.tryParse(controller.text) ?? 0.0;
        double diff = currentVal - sysVal;
        
        return Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(label, style: const TextStyle(fontWeight: FontWeight.w600)),
                  Text('Sist: $systemStock  •  Dif: ${diff > 0 ? "+$diff" : diff}', 
                    style: TextStyle(
                      fontSize: 12, 
                      color: diff == 0 ? Colors.grey : (diff > 0 ? Colors.green : Colors.red),
                      fontWeight: diff == 0 ? FontWeight.normal : FontWeight.bold
                    )
                  ),
                ],
              ),
            ),
            SizedBox(
              width: 100,
              child: TextFormField(
                controller: controller,
                keyboardType: const TextInputType.numberWithOptions(decimal: true),
                textAlign: TextAlign.center,
                onChanged: (val) {
                  setStateRow(() {});
                },
                decoration: InputDecoration(
                  isDense: true,
                  contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                ),
              ),
            ),
          ],
        );
      },
    );
  }
}

// --- OPERATOR CONFORMITY SCREEN ---
class OperatorConformityScreen extends StatefulWidget {
  final String baseUrl;
  const OperatorConformityScreen({super.key, required this.baseUrl});

  @override
  State<OperatorConformityScreen> createState() => _OperatorConformityScreenState();
}

class _OperatorConformityScreenState extends State<OperatorConformityScreen> {
  List<dynamic> _pendingList = [];
  bool _isLoading = true;
  bool _isSubmitting = false;
  final _notesController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _fetchPending();
  }

  Future<void> _fetchPending() async {
    setState(() => _isLoading = true);
    try {
      final token = await _Storage.getToken();
      final response = await http.get(
        Uri.parse('${widget.baseUrl}/api/soplados/inventory/pending'),
        headers: {'Authorization': 'Bearer $token', 'Accept': 'application/json'},
      ).timeout(const Duration(seconds: 15));

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        setState(() {
          _pendingList = data['inventories'];
        });
      }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e')));
    } finally {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _acceptInventory(int id) async {
    setState(() => _isSubmitting = true);
    try {
      final token = await _Storage.getToken();
      final response = await http.post(
        Uri.parse('${widget.baseUrl}/api/soplados/inventory/$id/accept'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: json.encode({
          'operator_notes': _notesController.text,
        }),
      ).timeout(const Duration(seconds: 15));

      if (response.statusCode == 200) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Conformidad firmada. Stock ajustado en el sistema.'), backgroundColor: Colors.green));
          _notesController.clear();
          _fetchPending();
        }
      } else {
        final data = json.decode(response.body);
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(data['message'] ?? 'Error'), backgroundColor: Colors.red));
      }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red));
    } finally {
      setState(() => _isSubmitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FA),
      appBar: AppBar(
        title: const Text('Confirmar Inventarios'),
        backgroundColor: Colors.amber.shade800,
        foregroundColor: Colors.white,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _pendingList.isEmpty
              ? const Center(child: Text('No tienes inventarios pendientes de firmar'))
              : ListView.builder(
                  padding: const EdgeInsets.all(15),
                  itemCount: _pendingList.length,
                  itemBuilder: (context, index) {
                    final inv = _pendingList[index];
                    final date = inv['created_at'].toString().split('T')[0];
                    final time = inv['created_at'].toString().split('T')[1].substring(0, 5);
                    
                    return Card(
                      elevation: 3,
                      margin: const EdgeInsets.only(bottom: 15),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
                      child: ExpansionTile(
                        leading: CircleAvatar(backgroundColor: Colors.amber.shade100, child: const Icon(Icons.rate_review, color: Colors.orange)),
                        title: Text('Inventario #${inv['id']}', style: const TextStyle(fontWeight: FontWeight.bold)),
                        subtitle: Text('Contado por: ${inv['supervisor']['name']} \nFecha: $date  •  $time'),
                        children: [
                          Padding(
                            padding: const EdgeInsets.all(15),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                if (inv['notes'] != null && inv['notes'].toString().isNotEmpty) ...[
                                  Text('Notas Supervisor:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Colors.grey.shade700)),
                                  Text(inv['notes'], style: const TextStyle(fontStyle: FontStyle.italic, fontSize: 13)),
                                  const Divider(),
                                ],
                                const Text('RESUMEN DE CONTEO FISICO:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Colors.grey)),
                                const SizedBox(height: 8),
                                ...(inv['details'] as List).map((d) {
                                  bool isFinished = d['type'] == 'finished_product';
                                  
                                  return Container(
                                    margin: const EdgeInsets.only(bottom: 8),
                                    padding: const EdgeInsets.all(10),
                                    decoration: BoxDecoration(color: Colors.grey.shade50, borderRadius: BorderRadius.circular(10)),
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(d['product']['name'] ?? 'Producto', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                                        const SizedBox(height: 6),
                                        Row(
                                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                          children: [
                                            Column(
                                              crossAxisAlignment: CrossAxisAlignment.start,
                                              children: [
                                                Text(isFinished ? '1ra / Insumo' : 'Cantidad', style: const TextStyle(fontSize: 10, color: Colors.grey)),
                                                Text('Fís: ${d['counted_primera']} (Sist: ${d['system_stock_primera']})', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600)),
                                              ],
                                            ),
                                            if (isFinished && d['counted_segunda'] != null)
                                              Column(
                                                crossAxisAlignment: CrossAxisAlignment.start,
                                                children: [
                                                  const Text('2da Calidad', style: TextStyle(fontSize: 10, color: Colors.grey)),
                                                  Text('Fís: ${d['counted_segunda']} (Sist: ${d['system_stock_segunda']})', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600)),
                                                ],
                                              ),
                                            if (isFinished && d['counted_merma'] != null && d['counted_merma'] > 0)
                                              Column(
                                                crossAxisAlignment: CrossAxisAlignment.start,
                                                children: [
                                                  const Text('Merma', style: TextStyle(fontSize: 10, color: Colors.grey)),
                                                  Text('${d['counted_merma']} uds', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.red)),
                                                ],
                                              ),
                                          ],
                                        )
                                      ],
                                    ),
                                  );
                                }).toList(),
                                const Divider(),
                                const SizedBox(height: 5),
                                TextFormField(
                                  controller: _notesController,
                                  decoration: const InputDecoration(
                                    labelText: 'Observación del Operador (Opcional)',
                                    border: OutlineInputBorder(),
                                    isDense: true,
                                  ),
                                  maxLines: 2,
                                ),
                                const SizedBox(height: 15),
                                SizedBox(
                                  width: double.infinity,
                                  height: 48,
                                  child: ElevatedButton.icon(
                                    onPressed: _isSubmitting ? null : () => _acceptInventory(inv['id']),
                                    style: ElevatedButton.styleFrom(
                                      backgroundColor: Colors.green.shade700,
                                      foregroundColor: Colors.white,
                                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                    ),
                                    icon: const Icon(Icons.check_circle),
                                    label: const Text('ACEPTAR CONFORMIDAD Y FIRMAR', style: TextStyle(fontWeight: FontWeight.bold)),
                                  ),
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
}

// --- INVENTORY HISTORY SCREEN ---
class InventoryHistoryScreen extends StatefulWidget {
  final String baseUrl;
  const InventoryHistoryScreen({super.key, required this.baseUrl});

  @override
  State<InventoryHistoryScreen> createState() => _InventoryHistoryScreenState();
}

class _InventoryHistoryScreenState extends State<InventoryHistoryScreen> {
  List _logs = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _fetchHistory();
  }

  Future<void> _fetchHistory() async {
    setState(() => _isLoading = true);
    try {
      final token = await _Storage.getToken();
      final response = await http.get(
        Uri.parse('${widget.baseUrl}/api/soplados/inventory/history'),
        headers: {'Authorization': 'Bearer $token', 'Accept': 'application/json'},
      ).timeout(const Duration(seconds: 15));

      final data = json.decode(response.body);
      if (response.statusCode == 200 && data['success'] == true) {
        setState(() => _logs = data['data']['data']);
      }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e')));
    } finally {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FA),
      appBar: AppBar(
        title: const Text('Historial de Inventarios'),
        backgroundColor: Colors.blueGrey,
        foregroundColor: Colors.white,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _logs.isEmpty
              ? const Center(child: Text('No hay inventarios registrados'))
              : ListView.builder(
                  padding: const EdgeInsets.all(15),
                  itemCount: _logs.length,
                  itemBuilder: (context, index) {
                    final inv = _logs[index];
                    final isPending = inv['status'] == 'pending_acceptance';
                    final date = inv['created_at'].toString().split('T')[0];
                    final time = inv['created_at'].toString().split('T')[1].substring(0, 5);
                    
                    return Card(
                      elevation: 2,
                      margin: const EdgeInsets.only(bottom: 15),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
                      child: ExpansionTile(
                        leading: CircleAvatar(
                          backgroundColor: isPending ? Colors.amber.shade100 : Colors.green.shade100,
                          child: Icon(isPending ? Icons.pending : Icons.check_circle, color: isPending ? Colors.amber.shade800 : Colors.green.shade800),
                        ),
                        title: Text('Inventario #${inv['id']} - ${isPending ? "PENDIENTE" : "COMPLETADO"}', style: const TextStyle(fontWeight: FontWeight.bold)),
                        subtitle: Text('Fecha: $date • Hora: $time\nPor: ${inv['supervisor']['name']}'),
                        children: [
                          Padding(
                            padding: const EdgeInsets.all(15),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  children: [
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          const Text('SUPERVISOR', style: TextStyle(fontSize: 10, color: Colors.grey)),
                                          Text(inv['supervisor']['name'], style: const TextStyle(fontWeight: FontWeight.bold)),
                                        ],
                                      ),
                                    ),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          const Text('OPERARIO', style: TextStyle(fontSize: 10, color: Colors.grey)),
                                          Text(inv['operator'] != null ? inv['operator']['name'] : 'Sin firmar', style: const TextStyle(fontWeight: FontWeight.bold)),
                                        ],
                                      ),
                                    ),
                                  ],
                                ),
                                const Divider(height: 20),
                                if (inv['notes'] != null && inv['notes'].toString().isNotEmpty) ...[
                                  const Text('NOTAS SUPERVISOR:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 11, color: Colors.grey)),
                                  Text(inv['notes'], style: const TextStyle(fontStyle: FontStyle.italic)),
                                  const SizedBox(height: 10),
                                ],
                                if (inv['operator_notes'] != null && inv['operator_notes'].toString().isNotEmpty) ...[
                                  const Text('NOTAS OPERARIO:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 11, color: Colors.grey)),
                                  Text(inv['operator_notes'], style: const TextStyle(fontStyle: FontStyle.italic)),
                                  const SizedBox(height: 10),
                                ],
                                const Text('DETALLE DE PRODUCTOS:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 11, color: Colors.grey)),
                                const SizedBox(height: 6),
                                ...(inv['details'] as List).map((d) {
                                  bool isFinished = d['type'] == 'finished_product';
                                  double pSys = double.parse(d['system_stock_primera'].toString());
                                  double pCount = double.parse(d['counted_primera'].toString());
                                  double pDiff = pCount - pSys;
                                  
                                  return Padding(
                                    padding: const EdgeInsets.only(bottom: 6),
                                    child: Row(
                                      children: [
                                        Expanded(child: Text(d['product']['name'] ?? 'Producto', style: const TextStyle(fontSize: 13))),
                                        Text(
                                          '1ra: $pCount (${pDiff >= 0 ? "+$pDiff" : "$pDiff"})', 
                                          style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: pDiff == 0 ? Colors.black : (pDiff > 0 ? Colors.green : Colors.red))
                                        ),
                                        if (isFinished && d['system_stock_segunda'] != null) ...[
                                          const SizedBox(width: 8),
                                          Builder(builder: (context) {
                                            double sSys = double.parse(d['system_stock_segunda'].toString());
                                            double sCount = double.parse(d['counted_segunda'].toString());
                                            double sDiff = sCount - sSys;
                                            return Text(
                                              '2da: $sCount (${sDiff >= 0 ? "+$sDiff" : "$sDiff"})',
                                              style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: sDiff == 0 ? Colors.black : (sDiff > 0 ? Colors.green : Colors.red))
                                            );
                                          })
                                        ],
                                        if (isFinished && d['counted_merma'] != null && d['counted_merma'] > 0) ...[
                                          const SizedBox(width: 8),
                                          Text('Merma: ${d['counted_merma']}', style: const TextStyle(fontSize: 12, color: Colors.red, fontWeight: FontWeight.bold)),
                                        ]
                                      ],
                                    ),
                                  );
                                }).toList(),
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
}




