package com.omnest.child

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import com.omnest.child.ui.WelcomeScreen
import com.omnest.child.ui.theme.OmnestTheme
import dagger.hilt.android.AndroidEntryPoint

@AndroidEntryPoint
class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        setContent {
            OmnestTheme {
                // Phase 1 replaces this with the navigation graph (welcome -> pairing -> permissions).
                WelcomeScreen(onHaveCode = {})
            }
        }
    }
}
