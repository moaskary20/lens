package app.lens.lens

import android.os.Bundle
import android.view.WindowManager
import androidx.core.splashscreen.SplashScreen.Companion.installSplashScreen
import io.flutter.embedding.android.FlutterActivity
import io.flutter.embedding.engine.FlutterEngine
import io.flutter.plugin.common.MethodChannel

class MainActivity : FlutterActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        try {
            // Blank black system splash; drop exit animation so Flutter splash takes over cleanly.
            val splashScreen = installSplashScreen()
            splashScreen.setOnExitAnimationListener { splashView ->
                splashView.remove()
            }
        } catch (_: Throwable) {
            // Older / OEM builds can reject splash APIs — continue into Flutter anyway.
        }
        super.onCreate(savedInstanceState)
    }

    override fun configureFlutterEngine(flutterEngine: FlutterEngine) {
        super.configureFlutterEngine(flutterEngine)
        MethodChannel(flutterEngine.dartExecutor.binaryMessenger, "lens/secure_window")
            .setMethodCallHandler { call, result ->
                when (call.method) {
                    "enable" -> {
                        window.addFlags(WindowManager.LayoutParams.FLAG_SECURE)
                        result.success(null)
                    }
                    else -> result.notImplemented()
                }
            }
    }
}
