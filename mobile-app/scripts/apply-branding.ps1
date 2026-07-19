# Apply TNF Today logo to Android launcher icons + splash screens.
# Usage (from mobile-app folder):
#   powershell -ExecutionPolicy Bypass -File .\scripts\apply-branding.ps1

$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Drawing

$mobileApp = Split-Path $PSScriptRoot -Parent
$res = Join-Path $mobileApp 'android\app\src\main\res'
$brandingDir = Join-Path $mobileApp 'branding'
$storeDir = Join-Path $mobileApp 'store-assets'

$sourceCandidates = @(
    (Join-Path $brandingDir 'logo-tnf.png'),
    'C:\Users\user\.cursor\projects\f-Rohit-Development-tnf-part\assets\c__Users_user_AppData_Roaming_Cursor_User_workspaceStorage_empty-window_images_logo_tnf-6644e93f-0df3-4a28-a09a-af262de7cd79.png'
)

$source = $sourceCandidates | Where-Object { Test-Path $_ } | Select-Object -First 1
if (-not $source) {
    throw 'TNF logo not found. Place it at mobile-app/branding/logo-tnf.png'
}

New-Item -ItemType Directory -Force -Path $brandingDir | Out-Null
New-Item -ItemType Directory -Force -Path $storeDir | Out-Null

$brandingLogo = Join-Path $brandingDir 'logo-tnf.png'
$srcFull = [System.IO.Path]::GetFullPath($source)
$dstFull = [System.IO.Path]::GetFullPath($brandingLogo)
if ($srcFull -ne $dstFull) {
    Copy-Item -Force $source $brandingLogo
}
Write-Host "Source logo: $brandingLogo"

function New-Bitmap([int]$w, [int]$h, [System.Drawing.Color]$bg) {
    $bmp = New-Object System.Drawing.Bitmap $w, $h
    $g = [System.Drawing.Graphics]::FromImage($bmp)
    $g.Clear($bg)
    $g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::HighQuality
    $g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
    $g.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
    return @{ Bmp = $bmp; Graphics = $g }
}

function Save-Png([System.Drawing.Bitmap]$bmp, [string]$path) {
    $dir = Split-Path $path -Parent
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Force -Path $dir | Out-Null
    }
    $bmp.Save($path, [System.Drawing.Imaging.ImageFormat]::Png)
}

function Draw-CenteredLogo(
    [System.Drawing.Graphics]$g,
    [System.Drawing.Image]$logo,
    [int]$canvas,
    [double]$scale = 0.72
) {
    $target = [int]($canvas * $scale)
    $aspect = $logo.Width / [double]$logo.Height
    if ($aspect -ge 1) {
        $dw = $target
        $dh = [int]($target / $aspect)
    } else {
        $dh = $target
        $dw = [int]($target * $aspect)
    }
    $x = [int](($canvas - $dw) / 2)
    $y = [int](($canvas - $dh) / 2)
    $g.DrawImage($logo, $x, $y, $dw, $dh)
}

function Draw-Splash(
    [System.Drawing.Graphics]$g,
    [System.Drawing.Image]$logo,
    [int]$w,
    [int]$h,
    [double]$scale = 0.42
) {
    $maxW = [int]($w * $scale)
    $maxH = [int]($h * $scale)
    $aspect = $logo.Width / [double]$logo.Height
    $dw = $maxW
    $dh = [int]($maxW / $aspect)
    if ($dh -gt $maxH) {
        $dh = $maxH
        $dw = [int]($maxH * $aspect)
    }
    $x = [int](($w - $dw) / 2)
    $y = [int](($h - $dh) / 2)
    $g.DrawImage($logo, $x, $y, $dw, $dh)
}

$logo = [System.Drawing.Image]::FromFile($brandingLogo)
$white = [System.Drawing.Color]::White

# --- Launcher icons ---
$iconSizes = @{
    'mipmap-mdpi'    = 48
    'mipmap-hdpi'    = 72
    'mipmap-xhdpi'   = 96
    'mipmap-xxhdpi'  = 144
    'mipmap-xxxhdpi' = 192
}

# Adaptive foreground uses 108dp canvas; logo stays in safe zone (~66%)
$fgSizes = @{
    'mipmap-mdpi'    = 108
    'mipmap-hdpi'    = 162
    'mipmap-xhdpi'   = 216
    'mipmap-xxhdpi'  = 324
    'mipmap-xxxhdpi' = 432
}

foreach ($folder in $iconSizes.Keys) {
    $size = $iconSizes[$folder]
    $outDir = Join-Path $res $folder

    $legacy = New-Bitmap $size $size $white
    Draw-CenteredLogo $legacy.Graphics $logo $size 0.86
    Save-Png $legacy.Bmp (Join-Path $outDir 'ic_launcher.png')
    Save-Png $legacy.Bmp (Join-Path $outDir 'ic_launcher_round.png')
    $legacy.Graphics.Dispose()
    $legacy.Bmp.Dispose()

    $fgSize = $fgSizes[$folder]
    $fg = New-Bitmap $fgSize $fgSize $white
    Draw-CenteredLogo $fg.Graphics $logo $fgSize 0.66
    Save-Png $fg.Bmp (Join-Path $outDir 'ic_launcher_foreground.png')
    $fg.Graphics.Dispose()
    $fg.Bmp.Dispose()

    Write-Host "Icons -> $folder"
}

# --- Splash screens ---
$splashTargets = @(
    @{ Path = 'drawable\splash.png'; W = 480; H = 800 },
    @{ Path = 'drawable-port-mdpi\splash.png'; W = 320; H = 480 },
    @{ Path = 'drawable-port-hdpi\splash.png'; W = 480; H = 800 },
    @{ Path = 'drawable-port-xhdpi\splash.png'; W = 720; H = 1280 },
    @{ Path = 'drawable-port-xxhdpi\splash.png'; W = 1080; H = 1920 },
    @{ Path = 'drawable-port-xxxhdpi\splash.png'; W = 1440; H = 2560 },
    @{ Path = 'drawable-land-mdpi\splash.png'; W = 480; H = 320 },
    @{ Path = 'drawable-land-hdpi\splash.png'; W = 800; H = 480 },
    @{ Path = 'drawable-land-xhdpi\splash.png'; W = 1280; H = 720 },
    @{ Path = 'drawable-land-xxhdpi\splash.png'; W = 1920; H = 1080 },
    @{ Path = 'drawable-land-xxxhdpi\splash.png'; W = 2560; H = 1440 }
)

foreach ($t in $splashTargets) {
    $splash = New-Bitmap $t.W $t.H $white
    Draw-Splash $splash.Graphics $logo $t.W $t.H 0.38
    Save-Png $splash.Bmp (Join-Path $res $t.Path)
    $splash.Graphics.Dispose()
    $splash.Bmp.Dispose()
    Write-Host "Splash -> $($t.Path)"
}

# --- Play Store listing icon (512x512) ---
$storeIcon = New-Bitmap 512 512 $white
Draw-CenteredLogo $storeIcon.Graphics $logo 512 0.88
Save-Png $storeIcon.Bmp (Join-Path $storeDir 'play-icon-512.png')
$storeIcon.Graphics.Dispose()
$storeIcon.Bmp.Dispose()
Write-Host "Store icon -> store-assets\play-icon-512.png"

$logo.Dispose()
Write-Host ''
Write-Host 'Done. TNF branding applied to Android icons and splash screens.'
Write-Host 'Rebuild the app in Android Studio to see the change.'
