package com.omnest.child.ui

import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.navigation.NavDestination.Companion.hasRoute
import androidx.compose.runtime.remember
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.rememberNavController
import androidx.navigation.toRoute
import com.omnest.child.data.DeviceSession
import com.omnest.child.permissions.Requirement
import com.omnest.child.ui.home.HomeScreen
import com.omnest.child.ui.pairing.PairedScreen
import com.omnest.child.ui.pairing.PairingScreen
import com.omnest.child.ui.setup.SetupWizardScreen
import com.omnest.child.usage.UsageSyncWorker
import androidx.compose.ui.platform.LocalContext
import kotlinx.serialization.Serializable

@Serializable data object WelcomeRoute
@Serializable data object PairingRoute
@Serializable data class PairedRoute(val childName: String)

/** [only] = requirement names to fix (from Home); empty = full first-run setup incl. brand guide. */
@Serializable data class SetupRoute(val only: List<String> = emptyList())
@Serializable data object HomeRoute

@Composable
fun OmnestNavHost(session: DeviceSession) {
    val nav = rememberNavController()
    val paired by session.current.collectAsStateWithLifecycle()
    // Decided once at launch; pairing mid-session navigates explicitly instead of rebuilding the graph.
    val start: Any = remember { if (session.current.value != null) HomeRoute else WelcomeRoute }

    // Unpaired by the parent while the app was open: back to the start.
    LaunchedEffect(paired) {
        if (paired == null && nav.currentDestination?.hasRoute(HomeRoute::class) == true) {
            nav.navigate(WelcomeRoute) { popUpTo(nav.graph.id) { inclusive = true } }
        }
    }

    NavHost(navController = nav, startDestination = start) {
        composable<WelcomeRoute> {
            WelcomeScreen(onHaveCode = { nav.navigate(PairingRoute) })
        }
        composable<PairingRoute> {
            PairingScreen(
                onBack = { nav.popBackStack() },
                onPaired = { device ->
                    nav.navigate(PairedRoute(device.childName)) {
                        popUpTo(WelcomeRoute) { inclusive = true }
                    }
                },
            )
        }
        composable<PairedRoute> { entry ->
            PairedScreen(
                childName = entry.toRoute<PairedRoute>().childName,
                onContinue = {
                    nav.navigate(SetupRoute()) {
                        popUpTo(nav.graph.id) { inclusive = true }
                    }
                },
            )
        }
        composable<SetupRoute> { entry ->
            val only = entry.toRoute<SetupRoute>().only
                .mapNotNull { name -> Requirement.entries.firstOrNull { it.name == name } }
                .ifEmpty { null }
            val context = LocalContext.current
            SetupWizardScreen(
                only = only,
                onFinished = {
                    UsageSyncWorker.runNow(context) // usage access may have just been granted
                    nav.navigate(HomeRoute) {
                        popUpTo(nav.graph.id) { inclusive = true }
                    }
                },
            )
        }
        composable<HomeRoute> {
            HomeScreen(
                childName = paired?.childName.orEmpty(),
                onFixPermissions = { missing -> nav.navigate(SetupRoute(missing.map { it.name })) },
            )
        }
    }
}
