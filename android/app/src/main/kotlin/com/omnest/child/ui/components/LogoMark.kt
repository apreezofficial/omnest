package com.omnest.child.ui.components

import androidx.compose.foundation.Canvas
import androidx.compose.ui.Modifier
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.geometry.Size
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.Path
import androidx.compose.ui.graphics.StrokeCap
import androidx.compose.ui.graphics.StrokeJoin
import androidx.compose.ui.graphics.drawscope.Stroke
import androidx.compose.runtime.Composable
import com.omnest.child.ui.theme.OmnestTheme

/** Door arch in a nest with the accent knock dot. Same geometry as web/components/Logo.tsx (48x48 grid). */
@Composable
fun LogoMark(modifier: Modifier = Modifier, color: Color = OmnestTheme.colors.primary, dot: Color = OmnestTheme.colors.accent) {
    Canvas(modifier) {
        val u = size.minDimension / 48f
        val stroke = Stroke(width = 5f * u, cap = StrokeCap.Round, join = StrokeJoin.Round)

        val door = Path().apply {
            moveTo(14f * u, 38f * u)
            lineTo(14f * u, 22f * u)
            arcTo(
                rect = androidx.compose.ui.geometry.Rect(Offset(14f * u, 12f * u), Size(20f * u, 20f * u)),
                startAngleDegrees = 180f,
                sweepAngleDegrees = 180f,
                forceMoveTo = false,
            )
            lineTo(34f * u, 38f * u)
        }
        drawPath(door, color, style = stroke)

        val nest = Path().apply {
            moveTo(6f * u, 33f * u)
            cubicTo(14f * u, 43f * u, 34f * u, 43f * u, 42f * u, 33f * u)
        }
        drawPath(nest, color, style = stroke)

        drawCircle(dot, radius = 3.5f * u, center = Offset(28.5f * u, 27f * u))
    }
}
