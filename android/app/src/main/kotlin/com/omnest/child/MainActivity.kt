package com.omnest.child

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import com.omnest.child.data.DeviceSession
import com.omnest.child.ui.OmnestNavHost
import com.omnest.child.ui.theme.OmnestTheme
import dagger.hilt.android.AndroidEntryPoint
import javax.inject.Inject

@AndroidEntryPoint
class MainActivity : ComponentActivity() {

    @Inject lateinit var session: DeviceSession

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        setContent {
            OmnestTheme {
                OmnestNavHost(session)
            }
        }
    }
}
