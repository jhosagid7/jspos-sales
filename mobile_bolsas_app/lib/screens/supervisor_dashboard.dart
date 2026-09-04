import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:http/http.dart' as http;
import '../services/local_db.dart';
import '../constants.dart';
import 'camera_scanner_screen.dart';

class SupervisorDashboardScreen extends StatefulWidget {
  final String baseUrl;
  final String token;
  final String userName;

  const SupervisorDashboardScreen({
    super.key,
    required this.baseUrl,
    required this.token,
    required this.userName,
  });

  @override
  State<SupervisorDashboardScreen> createState() => _SupervisorDashboardScreenState();
}

class _SupervisorDashboardScreenState extends State<SupervisorDashboardScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  bool _isLoading = true;
  bool _isActionInProgress = false;

  List<dynamic> _pendingProductions = [];
  Map<String, dynamic> _pendingTotals = {};

  List<dynamic> _preStockItems = [];
  Map<String, dynamic> _preStockTotals = {};

  List<Map<String, dynamic>> _cachedProducts = [];

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _loadProducts();
    _fetchFeed();
    _fetchPreStock();
  }

  Future<void> _loadProducts() async {
    final prods = await LocalDatabaseService.instance.getCachedProducts();
    if (mounted) setState(() => _cachedProducts = prods);
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  String get _cleanUrl => widget.baseUrl.replaceAll(RegExp(r'/+$'), '');

  Map<String, String> get _authHeaders => {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ${widget.token}',
      };

  Future<void> _fetchFeed() async {
    setState(() => _isLoading = true);
    try {
      final res = await http
          .get(
            Uri.parse('$_cleanUrl/api/bag-factory/supervisor/feed?status=pending_review'),
            headers: _authHeaders,
          )
          .timeout(const Duration(seconds: 10));

      if (res.statusCode == 200) {
        final data = json.decode(res.body);
        if (mounted) {
          setState(() {
            _pendingProductions = data['data'] ?? [];
            _pendingTotals = data['totals'] ?? {};
          });
        }
      }
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Error de conexión con el VPS'), backgroundColor: Colors.red),
        );
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _fetchPreStock() async {
    try {
      final res = await http
          .get(
            Uri.parse('$_cleanUrl/api/bag-factory/supervisor/pre-stock'),
            headers: _authHeaders,
          )
          .timeout(const Duration(seconds: 10));

      if (res.statusCode == 200) {
        final data = json.decode(res.body);
        if (mounted) {
          setState(() {
            _preStockItems = data['data'] ?? [];
            _preStockTotals = data['totals'] ?? {};
          });
        }
      }
    } catch (_) {}
  }

  Future<void> _approveProduction(int id) async {
    setState(() => _isActionInProgress = true);
    try {
      final res = await http
          .post(
            Uri.parse('$_cleanUrl/api/bag-factory/supervisor/productions/$id/approve'),
            headers: _authHeaders,
          )
          .timeout(const Duration(seconds: 8));

      if (res.statusCode == 200) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('✅ Bulto aprobado para Pre-Levantamiento'), backgroundColor: Color(0xFF10B981)),
          );
        }
        await _fetchFeed();
        await _fetchPreStock();
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error al aprobar: $e'), backgroundColor: Colors.red),
        );
      }
    } finally {
      if (mounted) setState(() => _isActionInProgress = false);
    }
  }

  Future<void> _bulkApprove() async {
    if (_pendingProductions.isEmpty) return;

    final ids = _pendingProductions.map((p) => p['id'] as int).toList();

    setState(() => _isActionInProgress = true);
    try {
      final res = await http
          .post(
            Uri.parse('$_cleanUrl/api/bag-factory/supervisor/productions/bulk-approve'),
            headers: _authHeaders,
            body: json.encode({'production_ids': ids}),
          )
          .timeout(const Duration(seconds: 12));

      if (res.statusCode == 200) {
        final data = json.decode(res.body);
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('✅ ${data['message'] ?? 'Bultos aprobados exitosamente'}'),
              backgroundColor: const Color(0xFF10B981),
            ),
          );
        }
        await _fetchFeed();
        await _fetchPreStock();
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error al aprobar masivamente: $e'), backgroundColor: Colors.red),
        );
      }
    } finally {
      if (mounted) setState(() => _isActionInProgress = false);
    }
  }

  Future<Map<String, dynamic>?> _showProductSearchDialog({Map<String, dynamic>? initial}) async {
    return await showModalBottomSheet<Map<String, dynamic>>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        String query = '';
        return StatefulBuilder(
          builder: (modalCtx, setModalState) {
            final filtered = _cachedProducts.where((p) {
              final name = (p['name'] ?? '').toString().toLowerCase();
              final sku = (p['sku'] ?? '').toString().toLowerCase();
              final q = query.toLowerCase().trim();
              return q.isEmpty || name.contains(q) || sku.contains(q);
            }).toList();

            return Container(
              height: MediaQuery.of(ctx).size.height * 0.85,
              decoration: const BoxDecoration(
                color: Color(0xFF1C2541),
                borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
              ),
              padding: const EdgeInsets.all(16),
              child: Column(
                children: [
                  Container(
                    width: 40,
                    height: 4,
                    decoration: BoxDecoration(color: Colors.white24, borderRadius: BorderRadius.circular(2)),
                  ),
                  const SizedBox(height: 14),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Row(
                        children: [
                          const Icon(Icons.search_rounded, color: Color(0xFF38BDF8), size: 22),
                          const SizedBox(width: 8),
                          Text(
                            'Seleccionar Producto (${_cachedProducts.length})',
                            style: GoogleFonts.plusJakartaSans(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold),
                          ),
                        ],
                      ),
                      IconButton(
                        icon: const Icon(Icons.qr_code_scanner, color: Color(0xFF38BDF8)),
                        tooltip: 'Escanear Código / QR',
                        onPressed: () async {
                          final code = await Navigator.push<String>(
                            ctx,
                            MaterialPageRoute(builder: (_) => const CameraScannerScreen()),
                          );
                          if (code != null && code.isNotEmpty) {
                            final match = _cachedProducts.firstWhere(
                              (p) => (p['sku'] ?? '').toString().toLowerCase() == code.trim().toLowerCase() ||
                                     (p['name'] ?? '').toString().toLowerCase().contains(code.trim().toLowerCase()),
                              orElse: () => <String, dynamic>{},
                            );
                            if (match.isNotEmpty) {
                              Navigator.pop(ctx, match);
                            } else {
                              setModalState(() => query = code.trim());
                            }
                          }
                        },
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    autofocus: true,
                    style: const TextStyle(color: Colors.white),
                    decoration: InputDecoration(
                      hintText: 'Buscar por medida, nombre o SKU...',
                      hintStyle: const TextStyle(color: Colors.white38),
                      filled: true,
                      fillColor: const Color(0xFF0F172A),
                      prefixIcon: const Icon(Icons.filter_alt_outlined, color: Colors.white54),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                    ),
                    onChanged: (val) => setModalState(() => query = val),
                  ),
                  const SizedBox(height: 12),
                  Expanded(
                    child: filtered.isEmpty
                        ? const Center(
                            child: Text('No se encontraron productos', style: TextStyle(color: Colors.white38)),
                          )
                        : ListView.separated(
                            itemCount: filtered.length,
                            separatorBuilder: (_, __) => const Divider(height: 1, color: Colors.white10),
                            itemBuilder: (context, idx) {
                              final p = filtered[idx];
                              final isSelected = initial != null && initial['id'] == p['id'];
                              return ListTile(
                                dense: true,
                                contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                leading: Container(
                                  padding: const EdgeInsets.all(8),
                                  decoration: BoxDecoration(
                                    color: isSelected ? const Color(0xFF0284C7) : const Color(0xFF0F172A),
                                    borderRadius: BorderRadius.circular(8),
                                  ),
                                  child: Icon(Icons.inventory_2_outlined, color: isSelected ? Colors.white : const Color(0xFF38BDF8), size: 18),
                                ),
                                title: Text(p['name'] ?? '', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w600, fontSize: 13)),
                                subtitle: Text('SKU: ${p['sku'] ?? 'N/A'}', style: const TextStyle(color: Colors.white38, fontSize: 11)),
                                trailing: isSelected ? const Icon(Icons.check_circle, color: Color(0xFF10B981), size: 18) : null,
                                onTap: () => Navigator.pop(ctx, p),
                              );
                            },
                          ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  Future<void> _showAdjustWeightDialog(Map<String, dynamic> item) async {
    Map<String, dynamic> selectedProduct = {
      'id': item['product_id'],
      'name': item['product_name'] ?? 'Bolsa',
    };

    final found = _cachedProducts.firstWhere(
      (p) => p['id'] == item['product_id'],
      orElse: () => selectedProduct,
    );
    selectedProduct = Map<String, dynamic>.from(found);

    final weightCtrl = TextEditingController(text: item['weight'].toString());
    final qtyCtrl = TextEditingController(text: item['quantity'].toString());

    await showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (dialogCtx, setDialogState) => AlertDialog(
          backgroundColor: const Color(0xFF1E293B),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          title: Text('⚖️ Auditoría y Corrección en Báscula',
              style: GoogleFonts.plusJakartaSans(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16)),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Operario: ${item['operator_name'] ?? 'N/A'}',
                  style: GoogleFonts.plusJakartaSans(color: Colors.white70, fontSize: 12)),
              const SizedBox(height: 12),
              const Text('Tipo de Bolsa / Medida:', style: TextStyle(color: Colors.white70, fontSize: 11)),
              const SizedBox(height: 6),
              InkWell(
                onTap: () async {
                  final picked = await _showProductSearchDialog(initial: selectedProduct);
                  if (picked != null) {
                    setDialogState(() => selectedProduct = picked);
                  }
                },
                borderRadius: BorderRadius.circular(10),
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                  decoration: BoxDecoration(
                    color: const Color(0xFF0F172A),
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: const Color(0xFF38BDF8).withOpacity(0.5)),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.inventory_2_outlined, color: Color(0xFF38BDF8), size: 18),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          selectedProduct['name'] ?? 'Seleccionar bolsa',
                          style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13),
                        ),
                      ),
                      const Icon(Icons.arrow_drop_down, color: Colors.white54),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 14),
              Row(
                children: [
                  Expanded(
                    flex: 2,
                    child: TextField(
                      controller: qtyCtrl,
                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                      style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
                      decoration: InputDecoration(
                        labelText: 'Cantidad',
                        labelStyle: const TextStyle(color: Colors.white54),
                        filled: true,
                        fillColor: const Color(0xFF0F172A),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    flex: 3,
                    child: TextField(
                      controller: weightCtrl,
                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                      style: const TextStyle(color: Colors.amberAccent, fontSize: 18, fontWeight: FontWeight.bold),
                      decoration: InputDecoration(
                        labelText: 'Peso Báscula',
                        labelStyle: const TextStyle(color: Colors.white54),
                        suffixText: 'Kg',
                        suffixStyle: const TextStyle(color: Colors.amberAccent, fontWeight: FontWeight.bold),
                        filled: true,
                        fillColor: const Color(0xFF0F172A),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('CANCELAR', style: TextStyle(color: Colors.white54)),
            ),
            ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF0284C7),
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
              ),
              onPressed: () async {
                final newWeight = double.tryParse(weightCtrl.text.trim()) ?? 0.0;
                final newQty = double.tryParse(qtyCtrl.text.trim()) ?? 0.0;
                if (newWeight <= 0 || newQty <= 0) return;

                Navigator.pop(ctx);
                setState(() => _isActionInProgress = true);
                try {
                  final res = await http.put(
                    Uri.parse('$_cleanUrl/api/bag-factory/supervisor/productions/${item['id']}'),
                    headers: _authHeaders,
                    body: json.encode({
                      'product_id': selectedProduct['id'],
                      'weight': newWeight,
                      'quantity': newQty,
                    }),
                  );
                  if (res.statusCode == 200) {
                    await _fetchFeed();
                  }
                } catch (_) {}
                if (mounted) setState(() => _isActionInProgress = false);
              },
              child: const Text('GUARDAR CAMBIOS'),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _showRejectDialog(Map<String, dynamic> item) async {
    final reasonCtrl = TextEditingController();

    await showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: const Color(0xFF1E293B),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Text('❌ Rechazar Bulto',
            style: GoogleFonts.plusJakartaSans(color: const Color(0xFFEF4444), fontWeight: FontWeight.bold)),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Indique el motivo por el cual no se aprueba este bulto:',
                style: GoogleFonts.plusJakartaSans(color: Colors.white70, fontSize: 12)),
            const SizedBox(height: 14),
            TextField(
              controller: reasonCtrl,
              maxLines: 3,
              style: const TextStyle(color: Colors.white),
              decoration: InputDecoration(
                hintText: 'Ej: Bolsa perforada, fuera de micraje o mal sellada',
                hintStyle: const TextStyle(color: Colors.white30, fontSize: 12),
                filled: true,
                fillColor: const Color(0xFF0F172A),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('CANCELAR', style: TextStyle(color: Colors.white54)),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFFEF4444)),
            onPressed: () async {
              if (reasonCtrl.text.trim().isEmpty) return;
              Navigator.pop(ctx);
              setState(() => _isActionInProgress = true);
              try {
                final res = await http.post(
                  Uri.parse('$_cleanUrl/api/bag-factory/supervisor/productions/${item['id']}/reject'),
                  headers: _authHeaders,
                  body: json.encode({'rejection_reason': reasonCtrl.text.trim()}),
                );
                if (res.statusCode == 200) {
                  await _fetchFeed();
                }
              } catch (_) {}
              if (mounted) setState(() => _isActionInProgress = false);
            },
            child: const Text('RECHAZAR', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
  }

  Future<void> _showTicketModal(int id) async {
    setState(() => _isActionInProgress = true);
    try {
      final res = await http
          .get(
            Uri.parse('$_cleanUrl/api/bag-factory/supervisor/ticket/$id'),
            headers: _authHeaders,
          )
          .timeout(const Duration(seconds: 8));

      if (res.statusCode == 200) {
        final ticket = json.decode(res.body)['data'];
        if (!mounted) return;

        await showModalBottomSheet(
          context: context,
          backgroundColor: Colors.transparent,
          isScrollControlled: true,
          builder: (ctx) => Container(
            margin: const EdgeInsets.all(16),
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(20),
              boxShadow: const [
                BoxShadow(color: Color(0x66000000), blurRadius: 20, offset: Offset(0, 10)),
              ],
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  width: 40,
                  height: 4,
                  decoration: BoxDecoration(
                    color: Colors.grey.shade300,
                    borderRadius: BorderRadius.circular(10),
                  ),
                ),
                const SizedBox(height: 16),
                Text('JSBOLSAS PRO',
                    style: GoogleFonts.plusJakartaSans(
                        fontSize: 18, fontWeight: FontWeight.w900, color: Colors.black)),
                Text('ETIQUETA TÉRMICA DE CONTROL',
                    style: GoogleFonts.plusJakartaSans(fontSize: 11, color: Colors.grey.shade600)),
                const Divider(thickness: 1.5, height: 24),
                // QR Mock box or QR visual
                Container(
                  width: 140,
                  height: 140,
                  decoration: BoxDecoration(
                    border: Border.all(color: Colors.black87, width: 2),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Icon(Icons.qr_code_2_rounded, size: 90, color: Colors.black),
                      Text(ticket['qr_code'] ?? '',
                          style: const TextStyle(
                              fontSize: 10, fontWeight: FontWeight.bold, color: Colors.black87)),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                _ticketRow('Producto:', ticket['product_name']),
                _ticketRow('Operario:', ticket['operator_name']),
                _ticketRow('Turno:', ticket['shift_type']),
                _ticketRow('Peso Total:', '${ticket['weight']} Kg'),
                _ticketRow('Fecha/Hora:', ticket['recorded_at']),
                _ticketRow('Auditado por:', ticket['reviewed_by']),
                const Divider(thickness: 1.5, height: 24),
                SizedBox(
                  width: double.infinity,
                  height: 46,
                  child: ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF1B263B),
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                    icon: const Icon(Icons.print_rounded),
                    label: const Text('IMPRIMIR ETIQUETA / COMPARTIR'),
                    onPressed: () {
                      Navigator.pop(ctx);
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('ðŸ–¨ï¸ Enviando etiqueta a impresora térmica...')),
                      );
                    },
                  ),
                ),
              ],
            ),
          ),
        );
      }
    } catch (_) {}
    if (mounted) setState(() => _isActionInProgress = false);
  }

  Widget _ticketRow(String label, dynamic value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 2),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(fontSize: 12, color: Colors.black54)),
          Text(value?.toString() ?? '-',
              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.black)),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0B132B),
      appBar: AppBar(
        backgroundColor: const Color(0xFF1C2541),
        elevation: 0,
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Supervisión de Planta v$kAppVersion',
                style: GoogleFonts.plusJakartaSans(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold)),
            Text('Jefe de Operaciones: ${widget.userName}',
                style: GoogleFonts.plusJakartaSans(color: Colors.white70, fontSize: 11)),
          ],
        ),
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: const Color(0xFF38BDF8),
          labelColor: const Color(0xFF38BDF8),
          unselectedLabelColor: Colors.white60,
          tabs: [
            Tab(
              icon: const Icon(Icons.fact_check_outlined, size: 18),
              text: 'Auditoría (${_pendingProductions.length})',
            ),
            Tab(
              icon: const Icon(Icons.inventory_rounded, size: 18),
              text: 'Pre-Levantamiento (${_preStockItems.length})',
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh, color: Colors.white),
            onPressed: () {
              _fetchFeed();
              _fetchPreStock();
            },
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFF38BDF8)))
          : TabBarView(
              controller: _tabController,
              children: [
                _buildAuditingTab(),
                _buildPreStockTab(),
              ],
            ),
    );
  }

  Widget _buildAuditingTab() {
    final totalWeight = (_pendingTotals['total_weight'] ?? 0.0) as num;
    

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // KPI summary card
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFF1E293B), Color(0xFF0F172A)],
              ),
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: Colors.white12),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                Column(
                  children: [
                    Text('Bultos por Auditar',
                        style: GoogleFonts.plusJakartaSans(color: Colors.white54, fontSize: 11)),
                    Text('${_pendingProductions.length}',
                        style: GoogleFonts.plusJakartaSans(
                            color: const Color(0xFFF59E0B), fontSize: 20, fontWeight: FontWeight.bold)),
                  ],
                ),
                Container(width: 1, height: 35, color: Colors.white12),
                Column(
                  children: [
                    Text('Kilos en Espera',
                        style: GoogleFonts.plusJakartaSans(color: Colors.white54, fontSize: 11)),
                    Text('${totalWeight.toStringAsFixed(2)} Kg',
                        style: GoogleFonts.plusJakartaSans(
                            color: const Color(0xFF38BDF8), fontSize: 20, fontWeight: FontWeight.bold)),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 14),
          if (_pendingProductions.isNotEmpty)
            SizedBox(
              width: double.infinity,
              height: 44,
              child: ElevatedButton.icon(
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF10B981),
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                ),
                icon: const Icon(Icons.done_all_rounded),
                label: const Text('APROBAR TODOS LOS BULTOS PENDIENTES'),
                onPressed: _isActionInProgress ? null : _bulkApprove,
              ),
            ),
          const SizedBox(height: 16),
          if (_pendingProductions.isEmpty)
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(32),
              decoration: BoxDecoration(
                color: const Color(0x4D1C2541),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Center(
                child: Text('ðŸŽ‰ No hay bultos pendientes de auditoría en este momento.',
                    style: GoogleFonts.plusJakartaSans(color: Colors.white54, fontSize: 13)),
              ),
            )
          else
            ListView.separated(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: _pendingProductions.length,
              separatorBuilder: (context, index) => const SizedBox(height: 10),
              itemBuilder: (ctx, idx) {
                final item = _pendingProductions[idx];
                final isDiurno = item['shift_type'] == 'diurno';
                final hasAdjusted = item['original_weight'] != null;

                return Container(
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: const Color(0xFF1C2541),
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(color: Colors.white10),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Row(
                            children: [
                              Icon(isDiurno ? Icons.wb_sunny_rounded : Icons.nightlight_round,
                                  color: Colors.amberAccent, size: 16),
                              const SizedBox(width: 6),
                              Text('Operario: ${item['operator_name']}',
                                  style: GoogleFonts.plusJakartaSans(
                                      color: Colors.white, fontSize: 13, fontWeight: FontWeight.bold)),
                            ],
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                            decoration: BoxDecoration(
                              color: const Color(0x33F59E0B),
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: const Text('Pendiente Báscula',
                                style: TextStyle(color: Color(0xFFF59E0B), fontSize: 10, fontWeight: FontWeight.bold)),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Text(item['product_name'] ?? 'Bolsa',
                          style: GoogleFonts.plusJakartaSans(
                              color: const Color(0xFF38BDF8), fontSize: 15, fontWeight: FontWeight.w600)),
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          Text('${item['quantity']} bulto(s)  •  ${item['weight']} Kg',
                              style: const TextStyle(
                                  color: Colors.white, fontSize: 13, fontWeight: FontWeight.bold)),
                          if (hasAdjusted) ...[
                            const SizedBox(width: 8),
                            Text('(Antes: ${item['original_weight']} Kg)',
                                style: const TextStyle(
                                    color: Colors.white38,
                                    fontSize: 11,
                                    decoration: TextDecoration.lineThrough)),
                          ],
                        ],
                      ),
                      const Divider(color: Colors.white10, height: 20),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.end,
                        children: [
                          OutlinedButton.icon(
                            style: OutlinedButton.styleFrom(
                              foregroundColor: const Color(0xFF38BDF8),
                              side: const BorderSide(color: Color(0xFF38BDF8)),
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                              visualDensity: VisualDensity.compact,
                            ),
                            icon: const Icon(Icons.scale, size: 14),
                            label: const Text('Báscula', style: TextStyle(fontSize: 11)),
                            onPressed: () => _showAdjustWeightDialog(item),
                          ),
                          const SizedBox(width: 8),
                          OutlinedButton.icon(
                            style: OutlinedButton.styleFrom(
                              foregroundColor: const Color(0xFFEF4444),
                              side: const BorderSide(color: Color(0xFFEF4444)),
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                              visualDensity: VisualDensity.compact,
                            ),
                            icon: const Icon(Icons.close, size: 14),
                            label: const Text('Rechazar', style: TextStyle(fontSize: 11)),
                            onPressed: () => _showRejectDialog(item),
                          ),
                          const SizedBox(width: 8),
                          ElevatedButton.icon(
                            style: ElevatedButton.styleFrom(
                              backgroundColor: const Color(0xFF10B981),
                              foregroundColor: Colors.white,
                              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                              visualDensity: VisualDensity.compact,
                            ),
                            icon: const Icon(Icons.check, size: 14),
                            label: const Text('Aprobar', style: TextStyle(fontSize: 11)),
                            onPressed: () => _approveProduction(item['id']),
                          ),
                        ],
                      ),
                    ],
                  ),
                );
              },
            ),
        ],
      ),
    );
  }

  Widget _buildPreStockTab() {
    final totalWeight = (_preStockTotals['total_weight'] ?? 0.0) as num;
    final totalPkgs = (_preStockTotals['total_packages'] ?? 0.0) as num;

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Pre-Stock KPI
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFF065F46), Color(0xFF047857)],
              ),
              borderRadius: BorderRadius.circular(16),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                Column(
                  children: [
                    Text('Bultos Aprobados',
                        style: GoogleFonts.plusJakartaSans(color: Colors.white70, fontSize: 11)),
                    Text(totalPkgs.toStringAsFixed(0),
                        style: GoogleFonts.plusJakartaSans(
                            color: Colors.white, fontSize: 22, fontWeight: FontWeight.bold)),
                  ],
                ),
                Container(width: 1, height: 35, color: Colors.white30),
                Column(
                  children: [
                    Text('Kilos en Pre-Stock',
                        style: GoogleFonts.plusJakartaSans(color: Colors.white70, fontSize: 11)),
                    Text('${totalWeight.toStringAsFixed(2)} Kg',
                        style: GoogleFonts.plusJakartaSans(
                            color: Colors.amberAccent, fontSize: 22, fontWeight: FontWeight.bold)),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          Text('Stock Listo para Almacén General (${_preStockItems.length})',
              style: GoogleFonts.plusJakartaSans(color: Colors.white70, fontSize: 13, fontWeight: FontWeight.w600)),
          const SizedBox(height: 10),
          if (_preStockItems.isEmpty)
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(32),
              decoration: BoxDecoration(
                color: const Color(0x4D1C2541),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Center(
                child: Text('No hay bultos en Pre-Levantamiento aún.',
                    style: GoogleFonts.plusJakartaSans(color: Colors.white38, fontSize: 12)),
              ),
            )
          else
            ListView.separated(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: _preStockItems.length,
              separatorBuilder: (context, index) => const SizedBox(height: 8),
              itemBuilder: (ctx, idx) {
                final item = _preStockItems[idx];
                final prodName = item['product']?['name'] ?? 'Bolsa';
                final opName = item['user']?['name'] ?? 'Operario';

                return Container(
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                  decoration: BoxDecoration(
                    color: const Color(0xFF1C2541),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: Colors.white10),
                  ),
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(8),
                        decoration: BoxDecoration(
                          color: const Color(0xFF0F172A),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: const Icon(Icons.qr_code_2, color: Color(0xFF10B981), size: 24),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(prodName,
                                style: GoogleFonts.plusJakartaSans(
                                    color: Colors.white, fontSize: 13, fontWeight: FontWeight.bold)),
                            Text('Operario: $opName  •  ${item['quantity']} bultos (${item['weight']} Kg)',
                                style: const TextStyle(color: Colors.white60, fontSize: 11)),
                            Text('Código: ${item['qr_code'] ?? 'S/C'}',
                                style: const TextStyle(color: Color(0xFF38BDF8), fontSize: 10, fontWeight: FontWeight.bold)),
                          ],
                        ),
                      ),
                      IconButton(
                        icon: const Icon(Icons.print_rounded, color: Colors.white70, size: 20),
                        onPressed: () => _showTicketModal(item['id']),
                      ),
                    ],
                  ),
                );
              },
            ),
        ],
      ),
    );
  }
}