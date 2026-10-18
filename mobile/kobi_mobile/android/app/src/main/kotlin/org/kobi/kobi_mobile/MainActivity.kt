package org.kobi.kobi_mobile

import io.flutter.embedding.android.FlutterActivity
import android.os.Bundle
import android.view.WindowManager
import android.os.SystemClock
import io.flutter.embedding.engine.FlutterEngine
import io.flutter.plugin.common.MethodChannel

class MainActivity : FlutterActivity() {
    override fun configureFlutterEngine(flutterEngine: FlutterEngine) {
        super.configureFlutterEngine(flutterEngine)
        MethodChannel(flutterEngine.dartExecutor.binaryMessenger, "org.kobi.mobile/secure_session")
            .setMethodCallHandler { call, result ->
                if (call.method != "flush") {
                    result.notImplemented()
                } else {
                    Thread {
                        // flutter_secure_storage 11.2.0's default encrypted store.
                        // commit() also waits for outstanding apply() writes.
                        val flushed = try {
                            getSharedPreferences("FlutterSecureStorage", MODE_PRIVATE).edit()
                                .putLong("__kobi_durability", SystemClock.uptimeMillis()).commit()
                        } catch (_: Exception) { false }
                        runOnUiThread { result.success(flushed) }
                    }.start()
                }
            }
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        window.addFlags(WindowManager.LayoutParams.FLAG_SECURE)
    }
}
