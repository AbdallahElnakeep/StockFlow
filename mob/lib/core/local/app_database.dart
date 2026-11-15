import 'dart:convert';
import 'package:path/path.dart' as p;
import 'package:sqflite/sqflite.dart';

class AppDatabase {
  static const _version = 1;
  final bool inMemory;
  Database? _db;

  AppDatabase({this.inMemory = false});

  Future<Database> get database async => _db ??= await open();

  Future<Database> open() async {
    if (_db != null) return _db!;
    final path = inMemory
        ? ':memory:'
        : p.join(await getDatabasesPath(), 'stockflow.db');
    _db = await openDatabase(
      path,
      version: _version,
      onCreate: (db, v) async {
        await db.execute('''
          CREATE TABLE pending_ops (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            op_uuid TEXT NOT NULL UNIQUE,
            type TEXT NOT NULL,
            payload TEXT NOT NULL,
            created_at TEXT NOT NULL
          )
        ''');
        await db.execute('''
          CREATE TABLE cache_kv (
            key TEXT PRIMARY KEY,
            value TEXT NOT NULL,
            updated_at TEXT NOT NULL
          )
        ''');
      },
    );
    return _db!;
  }

  Future<void> putCache(String key, String value) async {
    final db = await database;
    await db.insert('cache_kv', {
      'key': key,
      'value': value,
      'updated_at': DateTime.now().toIso8601String(),
    }, conflictAlgorithm: ConflictAlgorithm.replace);
  }

  Future<String?> getCache(String key) async {
    final db = await database;
    final rows = await db.query('cache_kv', where: 'key = ?', whereArgs: [key], limit: 1);
    return rows.isEmpty ? null : rows.first['value'] as String?;
  }

  Future<void> enqueueOp(String opUuid, String type, Map<String, dynamic> payload) async {
    final db = await database;
    await db.insert('pending_ops', {
      'op_uuid': opUuid,
      'type': type,
      'payload': _encode(payload),
      'created_at': DateTime.now().toIso8601String(),
    }, conflictAlgorithm: ConflictAlgorithm.ignore);
  }

  Future<List<Map<String, dynamic>>> pendingOps() async {
    final db = await database;
    return db.query('pending_ops', orderBy: 'created_at ASC');
  }

  Future<int> pendingCount() async {
    final db = await database;
    final count = Sqflite.firstIntValue(await db.rawQuery('SELECT COUNT(*) FROM pending_ops'));
    return count ?? 0;
  }

  Future<void> removeOp(String opUuid) async {
    final db = await database;
    await db.delete('pending_ops', where: 'op_uuid = ?', whereArgs: [opUuid]);
  }

  Future<void> clearAll() async {
    final db = await database;
    await db.delete('pending_ops');
    await db.delete('cache_kv');
  }

  String _encode(Map<String, dynamic> payload) => jsonEncode(payload);
}
