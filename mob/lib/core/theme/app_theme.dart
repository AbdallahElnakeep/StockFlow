import 'package:flutter/material.dart';

class AppColors {
  static const brand = Color(0xFF0066FF);
  static const brandHover = Color(0xFF0057D9);
  static const brandBg = Color(0xFFEEF4FF);
  static const chrome = Color(0xFF0B1E3D);
  static const surface = Color(0xFFF6F7F9);
  static const card = Color(0xFFFFFFFF);
  static const text1 = Color(0xFF101828);
  static const text2 = Color(0xFF475467);
  static const text3 = Color(0xFF667085);
  static const hair = Color(0xFFF2F4F7);
  static const border = Color(0xFFE4E7EC);
  static const borderStrong = Color(0xFFD0D5DD);
  static const success = Color(0xFF067647);
  static const successBg = Color(0xFFECFDF3);
  static const error = Color(0xFFB42318);
  static const errorBg = Color(0xFFFEF3F2);
  static const warning = Color(0xFFB54708);
  static const warningBg = Color(0xFFFFFAEB);
  static const info = Color(0xFF175CD3);
  static const infoBg = Color(0xFFEFF8FF);
}

class AppTheme {
  static ThemeData get light => ThemeData(
        useMaterial3: true,
        fontFamily: 'Cairo',
        scaffoldBackgroundColor: AppColors.surface,
        colorScheme: const ColorScheme.light(
          primary: AppColors.brand,
          onPrimary: Colors.white,
          secondary: AppColors.brand,
          surface: AppColors.card,
          error: AppColors.error,
        ),
        appBarTheme: const AppBarTheme(
          backgroundColor: AppColors.chrome,
          foregroundColor: Colors.white,
          centerTitle: true,
          elevation: 0,
        ),
        inputDecorationTheme: InputDecorationTheme(
          filled: true,
          fillColor: AppColors.card,
          contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(6),
            borderSide: const BorderSide(color: Color(0xFFCDD3DD)),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(6),
            borderSide: const BorderSide(color: Color(0xFFCDD3DD)),
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(6),
            borderSide: const BorderSide(color: AppColors.brand, width: 1.5),
          ),
          labelStyle: const TextStyle(fontSize: 13, color: AppColors.text2),
        ),
        elevatedButtonTheme: ElevatedButtonThemeData(
          style: ElevatedButton.styleFrom(
            backgroundColor: AppColors.brand,
            foregroundColor: Colors.white,
            minimumSize: const Size.fromHeight(46),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(6)),
            textStyle: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14),
          ),
        ),
        outlinedButtonTheme: OutlinedButtonThemeData(
          style: OutlinedButton.styleFrom(
            foregroundColor: AppColors.text1,
            minimumSize: const Size.fromHeight(46),
            side: const BorderSide(color: AppColors.borderStrong),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(6)),
          ),
        ),
        cardTheme: CardThemeData(
          color: AppColors.card,
          elevation: 0.5,
          margin: EdgeInsets.zero,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(10),
            side: const BorderSide(color: AppColors.border),
          ),
        ),
        listTileTheme: const ListTileThemeData(
          contentPadding: EdgeInsets.symmetric(horizontal: 14, vertical: 2),
          titleTextStyle: TextStyle(fontSize: 14, fontWeight: FontWeight.w600, color: AppColors.text1),
          subtitleTextStyle: TextStyle(fontSize: 12.5, color: AppColors.text3),
        ),
        dividerTheme: const DividerThemeData(color: AppColors.hair, thickness: 1, space: 1),
      );
}
