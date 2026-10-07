package com.omnest.child

import com.omnest.child.permissions.OemGuide
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNull
import org.junit.Test

class OemGuideTest {

    @Test
    fun mapsNigerianMarketBrands() {
        assertEquals(OemGuide.Transsion, OemGuide.forThisPhone("TECNO"))
        assertEquals(OemGuide.Transsion, OemGuide.forThisPhone("INFINIX MOBILITY"))
        assertEquals(OemGuide.Transsion, OemGuide.forThisPhone("itel"))
        assertEquals(OemGuide.Xiaomi, OemGuide.forThisPhone("Xiaomi"))
        assertEquals(OemGuide.Samsung, OemGuide.forThisPhone("samsung"))
        assertEquals(OemGuide.Oppo, OemGuide.forThisPhone("realme"))
        assertEquals(OemGuide.Vivo, OemGuide.forThisPhone("vivo"))
    }

    @Test
    fun stockAndroidNeedsNoGuide() {
        assertNull(OemGuide.forThisPhone("Google"))
        assertNull(OemGuide.forThisPhone(""))
    }
}
