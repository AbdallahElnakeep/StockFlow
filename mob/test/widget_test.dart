import 'package:flutter_test/flutter_test.dart';
import 'package:stockflow_rep/core/theme/app_theme.dart';

void main() {
  test('app theme uses brand color and cairo font', () {
    final theme = AppTheme.light;

    expect(theme.colorScheme.primary.toARGB32(), 0xFF0066FF);
    expect(theme.textTheme.bodyLarge?.fontFamily, 'Cairo');
    expect(theme.scaffoldBackgroundColor.toARGB32(), 0xFFF6F7F9);
  });
}
