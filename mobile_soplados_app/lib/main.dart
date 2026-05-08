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
        body: {'email': _emailController.text, 'password': _passwordController.text, 'device_name': 'Mobile (Soplados)'},
      ).timeout(const Duration(seconds: 15));

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        final prefs = await SharedPreferences.getInstance();
        await prefs.setInt('user_id', data['user']['id'] ?? 0);
        await prefs.setString('token', data['access_token'] ?? '');
        await prefs.setString('user_name', data['user']['name'] ?? 'Operador Soplados');
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
  
  Map<String, dynamic>? _currentShift;

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
    }); 
    _fetchCurrentShift();
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
          IconButton(icon: const Icon(Icons.refresh), onPressed: _fetchCurrentShift),
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
        onRefresh: _fetchCurrentShift,
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
                    }),
                    _menuCard('Traspasos\nRechazados', Icons.assignment_return_rounded, Colors.orange, () {
                      Navigator.push(context, MaterialPageRoute(builder: (context) => TransfersScreen(baseUrl: _baseUrl)));
                    }),
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

  Widget _menuCard(String title, IconData icon, Color color, VoidCallback onTap) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(20),
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(20),
          boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 10, offset: const Offset(0, 5))]
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(15),
              decoration: BoxDecoration(color: color.withOpacity(0.12), shape: BoxShape.circle),
              child: Icon(icon, color: color, size: 35),
            ),
            const SizedBox(height: 15),
            Text(title, textAlign: TextAlign.center, style: TextStyle(fontSize: 14, fontWeight: FontWeight.w900, color: Colors.grey.shade800, height: 1.2))
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

class ProductionItem {
  final ProductSimple product;
  double quantity;
  String quality; // '1st', '2nd', 'damaged'
  ProductionItem({required this.product, this.quantity = 0.0, this.quality = '1st'});
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
  final List<ProductSimple> _allProducts = [];
  final List<ProductionItem> _materials = [];
  final List<ProductionItem> _outputs = [];
  bool _isLoading = false;
  final _notesController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _fetchProducts();
  }

  Future<void> _fetchProducts() async {
    setState(() => _isLoading = true);
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');
      final response = await http.get(Uri.parse('${widget.baseUrl}/api/products'), headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
      }).timeout(const Duration(seconds: 15));
      
      if (response.statusCode == 200) {
        final List data = json.decode(response.body);
        setState(() {
          _allProducts.clear();
          _allProducts.addAll(data.map((e) => ProductSimple.fromJson(e)).toList());
        });
      }
    } catch (e) { debugPrint("Fetch Prod Err: $e"); }
    finally { setState(() => _isLoading = false); }
  }

  void _addItem(bool isOutput) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (context) {
        ProductSimple? selectedProd;
        final qtyController = TextEditingController();
        String quality = '1st';

        return StatefulBuilder(
          builder: (context, setModalState) {
            return Padding(
              padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom, left: 20, right: 20, top: 20),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text('Agregar ${isOutput ? "Producto Terminado" : "Insumo"}', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 15),
                  DropdownButtonFormField<ProductSimple>(
                    decoration: const InputDecoration(labelText: 'Producto', border: OutlineInputBorder()),
                    items: _allProducts.map((p) => DropdownMenuItem(value: p, child: Text(p.name))).toList(),
                    onChanged: (v) => setModalState(() => selectedProd = v),
                  ),
                  const SizedBox(height: 15),
                  TextField(
                    controller: qtyController,
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(labelText: 'Cantidad', border: OutlineInputBorder()),
                  ),
                  if (isOutput) ...[
                    const SizedBox(height: 15),
                    const Text('Calidad / Estado'),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceAround,
                      children: [
                        _qualityRadio(setModalState, '1st', '1ra Calidad', quality, (v) => quality = v),
                        _qualityRadio(setModalState, '2nd', '2da Calidad', quality, (v) => quality = v),
                        _qualityRadio(setModalState, 'damaged', 'Merma', quality, (v) => quality = v),
                      ],
                    ),
                  ],
                  const SizedBox(height: 20),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: () {
                        if (selectedProd == null || qtyController.text.isEmpty) return;
                        setState(() {
                          final item = ProductionItem(
                            product: selectedProd!,
                            quantity: double.tryParse(qtyController.text) ?? 0.0,
                            quality: quality
                          );
                          if (isOutput) _outputs.add(item); else _materials.add(item);
                        });
                        Navigator.pop(context);
                      },
                      style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF2C3E50), foregroundColor: Colors.white),
                      child: const Text('AGREGAR'),
                    ),
                  ),
                  const SizedBox(height: 20),
                ],
              ),
            );
          }
        );
      }
    );
  }

  Widget _qualityRadio(StateSetter setState, String val, String label, String current, Function(String) onChanged) {
    return Column(
      children: [
        Radio<String>(value: val, groupValue: current, onChanged: (v) { setState(() => onChanged(v!)); }),
        Text(label, style: const TextStyle(fontSize: 10))
      ],
    );
  }

  Future<void> _submit() async {
    if (_outputs.isEmpty || _materials.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Debe agregar al menos un insumo y un producto')));
      return;
    }

    setState(() => _isLoading = true);
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');
      
      final body = {
        'shift_id': widget.shiftId,
        'notes': _notesController.text,
        'materials': _materials.map((m) => {'product_id': m.product.id, 'quantity': m.quantity}).toList(),
        'outputs': _outputs.map((o) => {'product_id': o.product.id, 'quantity': o.quantity, 'quality': o.quality}).toList(),
      };

      final response = await http.post(
        Uri.parse('${widget.baseUrl}/api/soplados/production'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: json.encode(body)
      ).timeout(const Duration(seconds: 20));

      if (response.statusCode == 200) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Producción registrada con éxito'), backgroundColor: Colors.green));
          Navigator.pop(context);
        }
      } else {
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: ${response.body}'), backgroundColor: Colors.red));
      }
    } catch (e) { debugPrint("Submit Err: $e"); }
    finally { setState(() => _isLoading = false); }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Registro de Producción'),
        backgroundColor: const Color(0xFF2C3E50),
        foregroundColor: Colors.white,
      ),
      body: _isLoading && _allProducts.isEmpty 
        ? const Center(child: CircularProgressIndicator())
        : SingleChildScrollView(
            padding: const EdgeInsets.all(15),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _sectionHeader('INSUMOS (Lo que se usó)', () => _addItem(false)),
                ..._materials.map((m) => _itemTile(m, () => setState(() => _materials.remove(m)))),
                const SizedBox(height: 20),
                _sectionHeader('PRODUCTO TERMINADO (Lo que salió)', () => _addItem(true)),
                ..._outputs.map((o) => _itemTile(o, () => setState(() => _outputs.remove(o)))),
                const SizedBox(height: 20),
                TextField(
                  controller: _notesController,
                  decoration: const InputDecoration(labelText: 'Notas / Observaciones', border: OutlineInputBorder()),
                  maxLines: 2,
                ),
                const SizedBox(height: 30),
                SizedBox(
                  width: double.infinity,
                  height: 55,
                  child: ElevatedButton(
                    onPressed: _isLoading ? null : _submit,
                    style: ElevatedButton.styleFrom(backgroundColor: Colors.green.shade700, foregroundColor: Colors.white),
                    child: _isLoading ? const CircularProgressIndicator(color: Colors.white) : const Text('REGISTRAR PRODUCCIÓN', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                  ),
                )
              ],
            ),
          ),
    );
  }

  Widget _sectionHeader(String title, VoidCallback onAdd) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF2C3E50))),
        IconButton(onPressed: onAdd, icon: const Icon(Icons.add_circle, color: Color(0xFF2C3E50))),
      ],
    );
  }

  Widget _itemTile(ProductionItem item, VoidCallback onRemove) {
    return Card(
      margin: const EdgeInsets.symmetric(vertical: 5),
      child: ListTile(
        title: Text(item.product.name),
        subtitle: Text('Cant: ${item.quantity} ${item.quality != '1st' ? "(${item.quality})" : ""}'),
        trailing: IconButton(icon: const Icon(Icons.delete, color: Colors.red), onPressed: onRemove),
      ),
    );
  }
}

// --- TRANSFERS SCREEN (REJECTIONS / RETURNS) ---
class TransfersScreen extends StatefulWidget {
  final String baseUrl;
  const TransfersScreen({super.key, required this.baseUrl});
  @override
  State<TransfersScreen> createState() => _TransfersScreenState();
}

class _TransfersScreenState extends State<TransfersScreen> {
  List<dynamic> _pendingReturns = [];
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _fetchReturns();
  }

  Future<void> _fetchReturns() async {
    setState(() => _isLoading = true);
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');
      final response = await http.get(Uri.parse('${widget.baseUrl}/api/soplados/transfers/returns/pending'), headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
      }).timeout(const Duration(seconds: 15));
      
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        setState(() { _pendingReturns = data['transfers']; });
      }
    } catch (e) { debugPrint("Fetch Returns Err: $e"); }
    finally { setState(() => _isLoading = false); }
  }

  Future<void> _receiveReturn(int id) async {
    setState(() => _isLoading = true);
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');
      final response = await http.post(
        Uri.parse('${widget.baseUrl}/api/soplados/transfers/$id/returns/receive'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        }
      ).timeout(const Duration(seconds: 15));
      
      if (response.statusCode == 200) {
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Mercancía reintegrada correctamente'), backgroundColor: Colors.green));
        _fetchReturns();
      } else {
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: ${response.body}'), backgroundColor: Colors.red));
      }
    } catch (e) { debugPrint("Receive Return Err: $e"); }
    finally { setState(() => _isLoading = false); }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Retornos de Traspaso'),
        backgroundColor: Colors.orange,
        foregroundColor: Colors.white,
      ),
      body: _isLoading && _pendingReturns.isEmpty 
        ? const Center(child: CircularProgressIndicator())
        : RefreshIndicator(
            onRefresh: _fetchReturns,
            child: _pendingReturns.isEmpty 
              ? const Center(child: Text('No hay retornos pendientes'))
              : ListView.builder(
                  padding: const EdgeInsets.all(10),
                  itemCount: _pendingReturns.length,
                  itemBuilder: (context, index) {
                    final t = _pendingReturns[index];
                    return Card(
                      child: ExpansionTile(
                        title: Text('Traspaso #${t['id']} - ${t['dest_warehouse_name']}'),
                        subtitle: Text('Motivo: ${t['rejection_reason'] ?? 'Sin motivo'}'),
                        children: [
                          Padding(
                            padding: const EdgeInsets.all(15),
                            child: Column(
                              children: [
                                ...(t['details'] as List).map((d) => ListTile(
                                  dense: true,
                                  title: Text(d['product_name']),
                                  trailing: Text('Rechazado: ${d['rejected_qty']}'),
                                )).toList(),
                                const SizedBox(height: 15),
                                SizedBox(
                                  width: double.infinity,
                                  child: ElevatedButton(
                                    onPressed: () => _receiveReturn(t['id']),
                                    style: ElevatedButton.styleFrom(backgroundColor: Colors.orange),
                                    child: const Text('CONFIRMAR RECEPCIÓN EN PLANTA', style: TextStyle(color: Colors.white)),
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


