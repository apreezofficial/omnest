package com.omnest.child.ui.theme

import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.ui.unit.dp

/** 4dp base unit; use only these steps (docs/specs.md §4). */
object OmnestSpacing {
    val s1 = 4.dp
    val s2 = 8.dp
    val s3 = 12.dp
    val s4 = 16.dp
    val s5 = 20.dp
    val s6 = 24.dp
    val s8 = 32.dp
    val s10 = 40.dp
    val s12 = 48.dp
    val s16 = 64.dp
    val s24 = 96.dp

    /** Screen horizontal padding on phones. */
    val screen = s5
}

/** §5 Radius. */
object OmnestShapes {
    val sm = RoundedCornerShape(8.dp)
    val md = RoundedCornerShape(12.dp)
    val lg = RoundedCornerShape(20.dp)
    val xl = RoundedCornerShape(28.dp)
    val full = RoundedCornerShape(percent = 50)
}

/** §6 Borders and hard shadows, §7 touch targets. */
object OmnestDimens {
    val borderBold = 2.dp
    val borderQuiet = 1.dp
    val shadowHard = 3.dp
    val shadowHardLg = 6.dp
    val touchTarget = 48.dp
    val buttonSm = 40.dp
    val buttonMd = 48.dp
    val buttonLg = 56.dp
    val knockButton = 64.dp
    val knockButtonMaxWidth = 320.dp
}
