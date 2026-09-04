import 'package:flutter/material.dart';

class QuantitySelector extends StatelessWidget {
  final int value;
  final int min;
  final int max;
  final ValueChanged<int> onChanged;
  final double buttonSize;
  final double fontSize;
  final Color activeColor;
  final Color? disabledColor;

  const QuantitySelector({
    super.key,
    required this.value,
    required this.min,
    required this.max,
    required this.onChanged,
    this.buttonSize = 32,
    this.fontSize = 14,
    this.activeColor = const Color(0xFFD4AF37),
    this.disabledColor,
  });

  @override
  Widget build(BuildContext context) {
    // Fix: Provide a default color if disabledColor is null
    final Color effectiveDisabledColor = disabledColor ?? Colors.grey[300]!;
    
    // Determine if buttons should be enabled
    final bool canDecrease = value > min;
    final bool canIncrease = value < max;

    return Container(
      decoration: BoxDecoration(
        border: Border.all(color: Colors.grey[300]!),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          // Decrease button
          _buildButton(
            icon: Icons.remove,
            onPressed: canDecrease ? () => onChanged(value - 1) : null,
            backgroundColor: canDecrease ? activeColor.withOpacity(0.1) : Colors.transparent,
            foregroundColor: canDecrease ? activeColor : effectiveDisabledColor,
          ),

          // Quantity display
          Container(
            width: 40,
            height: buttonSize,
            alignment: Alignment.center,
            child: Text(
              value.toString(),
              style: TextStyle(
                fontSize: fontSize,
                fontWeight: FontWeight.bold,
              ),
            ),
          ),

          // Increase button
          _buildButton(
            icon: Icons.add,
            onPressed: canIncrease ? () => onChanged(value + 1) : null,
            backgroundColor: canIncrease ? activeColor.withOpacity(0.1) : Colors.transparent,
            foregroundColor: canIncrease ? activeColor : effectiveDisabledColor,
          ),
        ],
      ),
    );
  }

  Widget _buildButton({
    required IconData icon,
    required VoidCallback? onPressed,
    required Color backgroundColor,
    required Color foregroundColor, // Now this is non-nullable
  }) {
    return Container(
      decoration: BoxDecoration(
        color: backgroundColor,
        borderRadius: BorderRadius.circular(6),
      ),
      child: IconButton(
        icon: Icon(icon, size: 16),
        onPressed: onPressed,
        color: foregroundColor,
        padding: EdgeInsets.zero,
        constraints: BoxConstraints(
          minWidth: buttonSize,
          minHeight: buttonSize,
        ),
        splashRadius: buttonSize / 2,
      ),
    );
  }
}

// Alternative: Horizontal version with larger buttons for cart
class CartQuantitySelector extends StatelessWidget {
  final int value;
  final int min;
  final int max;
  final ValueChanged<int> onChanged;
  final VoidCallback? onRemove;

  const CartQuantitySelector({
    super.key,
    required this.value,
    required this.min,
    required this.max,
    required this.onChanged,
    this.onRemove,
  });

  @override
  Widget build(BuildContext context) {
    final bool canDecrease = value > min;
    final bool canIncrease = value < max;
    final bool showRemove = value <= min && onRemove != null;

    return Container(
      decoration: BoxDecoration(
        border: Border.all(color: Colors.grey[300]!),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          // Decrease button (or remove if quantity is 1)
          if (showRemove)
            _buildCartButton(
              icon: Icons.delete_outline,
              onPressed: onRemove,
              color: Colors.red,
            )
          else
            _buildCartButton(
              icon: Icons.remove,
              onPressed: canDecrease ? () => onChanged(value - 1) : null,
              color: canDecrease ? const Color(0xFFD4AF37) : Colors.grey,
            ),

          // Quantity display
          Container(
            width: 40,
            height: 40,
            alignment: Alignment.center,
            child: Text(
              value.toString(),
              style: const TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.bold,
              ),
            ),
          ),

          // Increase button
          _buildCartButton(
            icon: Icons.add,
            onPressed: canIncrease ? () => onChanged(value + 1) : null,
            color: canIncrease ? const Color(0xFFD4AF37) : Colors.grey,
          ),
        ],
      ),
    );
  }

  Widget _buildCartButton({
    required IconData icon,
    required VoidCallback? onPressed,
    required Color color,
  }) {
    return Container(
      decoration: BoxDecoration(
        color: onPressed != null ? color.withOpacity(0.1) : Colors.transparent,
        borderRadius: BorderRadius.circular(6),
      ),
      child: IconButton(
        icon: Icon(icon, size: 18),
        onPressed: onPressed,
        color: onPressed != null ? color : Colors.grey[400],
        padding: EdgeInsets.zero,
        constraints: const BoxConstraints(
          minWidth: 40,
          minHeight: 40,
        ),
        splashRadius: 20,
      ),
    );
  }
}

// Simple counter widget for forms
class CounterWidget extends StatelessWidget {
  final int value;
  final int min;
  final int max;
  final ValueChanged<int> onChanged;
  final String? label;
  final String? unit;

  const CounterWidget({
    super.key,
    required this.value,
    required this.min,
    required this.max,
    required this.onChanged,
    this.label,
    this.unit,
  });

  @override
  Widget build(BuildContext context) {
    final bool canDecrease = value > min;
    final bool canIncrease = value < max;

    return Row(
      children: [
        if (label != null) ...[
          Text(
            label!,
            style: const TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w500,
            ),
          ),
          const SizedBox(width: 12),
        ],
        Container(
          decoration: BoxDecoration(
            border: Border.all(color: Colors.grey[300]!),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              _buildCounterButton(
                icon: Icons.remove,
                onPressed: canDecrease ? () => onChanged(value - 1) : null,
              ),
              Container(
                width: 50,
                height: 36,
                alignment: Alignment.center,
                child: Text(
                  value.toString(),
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
              _buildCounterButton(
                icon: Icons.add,
                onPressed: canIncrease ? () => onChanged(value + 1) : null,
              ),
            ],
          ),
        ),
        if (unit != null) ...[
          const SizedBox(width: 8),
          Text(
            unit!,
            style: TextStyle(
              fontSize: 12,
              color: Colors.grey[600],
            ),
          ),
        ],
      ],
    );
  }

  Widget _buildCounterButton({
    required IconData icon,
    required VoidCallback? onPressed,
  }) {
    return Container(
      decoration: BoxDecoration(
        color: onPressed != null ? const Color(0xFFD4AF37).withOpacity(0.1) : Colors.transparent,
        borderRadius: BorderRadius.circular(6),
      ),
      child: IconButton(
        icon: Icon(icon, size: 14),
        onPressed: onPressed,
        color: onPressed != null ? const Color(0xFFD4AF37) : Colors.grey[400],
        padding: EdgeInsets.zero,
        constraints: const BoxConstraints(
          minWidth: 36,
          minHeight: 36,
        ),
        splashRadius: 18,
      ),
    );
  }
}