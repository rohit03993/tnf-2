import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const androidDir = path.join(__dirname, '..', 'android');
const androidJavaDir = path.join(androidDir, 'app', 'src', 'main', 'java');

function findMainActivityFile(dir) {
    if (!fs.existsSync(dir)) {
        return null;
    }

    for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
        const fullPath = path.join(dir, entry.name);

        if (entry.isDirectory()) {
            const nested = findMainActivityFile(fullPath);
            if (nested) {
                return nested;
            }
        } else if (entry.name === 'MainActivity.java') {
            return fullPath;
        }
    }

    return null;
}

function patchMainActivity() {
    const mainActivityPath = findMainActivityFile(androidJavaDir);

    if (!mainActivityPath) {
        console.error('MainActivity.java not found. Run: npm install && npx cap add android');
        process.exit(1);
    }

    let source = fs.readFileSync(mainActivityPath, 'utf8');

    if (source.includes('TNFTodayCapacitor/1.0')) {
        console.log('MainActivity already patched.');
        return;
    }

    const imports = `import android.webkit.WebView;
`;

    if (!source.includes('import android.webkit.WebView;')) {
        source = source.replace(
            /(package [^;]+;\s*\n)/,
            `$1\n${imports}`,
        );
    }

    const patch = `
    @Override
    public void onStart() {
        super.onStart();
        WebView webView = this.bridge.getWebView();
        if (webView != null) {
            String userAgent = webView.getSettings().getUserAgentString();
            if (!userAgent.contains("TNFTodayCapacitor")) {
                webView.getSettings().setUserAgentString(userAgent + " TNFTodayCapacitor/1.0");
            }
        }
    }
`;

    if (!source.includes('public void onStart()')) {
        source = source.replace(
            /public class MainActivity extends BridgeActivity(?:\s*\{\s*\})?/,
            `public class MainActivity extends BridgeActivity {${patch}`,
        );
    }

    fs.writeFileSync(mainActivityPath, source);
    console.log('Patched MainActivity user-agent:', mainActivityPath);
}

function removeFlatDirBlock(source) {
    return source.replace(
        /\n\s*flatDir\s*\{[^}]*\}/gs,
        '',
    );
}

function patchGradleFile(relativePath) {
    const filePath = path.join(androidDir, relativePath);

    if (!fs.existsSync(filePath)) {
        return;
    }

    const original = fs.readFileSync(filePath, 'utf8');
    const updated = removeFlatDirBlock(original);

    if (updated !== original) {
        fs.writeFileSync(filePath, updated);
        console.log('Removed flatDir from:', relativePath);
    }
}

function patchGradleFiles() {
    patchGradleFile(path.join('app', 'build.gradle'));
    patchGradleFile(path.join('capacitor-cordova-android-plugins', 'build.gradle'));

    const rootGradle = path.join(androidDir, 'build.gradle');
    const lintBlock = `subprojects { project ->
    project.tasks.withType(JavaCompile).configureEach {
        options.compilerArgs << '-Xlint:-unchecked'
    }
}
`;

    if (fs.existsSync(rootGradle)) {
        let source = fs.readFileSync(rootGradle, 'utf8');

        if (!source.includes('-Xlint:-unchecked')) {
            source = `${source.trimEnd()}\n\n${lintBlock}\n`;
            fs.writeFileSync(rootGradle, source);
            console.log('Added Java compile lint suppression to android/build.gradle');
        }
    }
}

patchMainActivity();
patchGradleFiles();
