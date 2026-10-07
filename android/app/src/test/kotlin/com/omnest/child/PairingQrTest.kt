package com.omnest.child

import com.omnest.child.ui.pairing.parsePairingQr
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNull
import org.junit.Test

class PairingQrTest {

    @Test
    fun readsCodeFromOmnestUri() {
        assertEquals("482913", parsePairingQr("omnest://pair?code=482913"))
        assertEquals("000123", parsePairingQr("omnest://pair?v=1&code=000123"))
        assertEquals("482913", parsePairingQr("  omnest://pair?code=482913&x=y "))
    }

    @Test
    fun rejectsOtherQrCodes() {
        assertNull(parsePairingQr("https://example.com/?code=482913"))
        assertNull(parsePairingQr("omnest://pair?code=12345"))
        assertNull(parsePairingQr("omnest://pair?code=1234567"))
        assertNull(parsePairingQr("omnest://pair?xcode=482913"))
    }
}
