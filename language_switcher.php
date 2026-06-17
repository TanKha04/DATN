<?php
require_once 'translator.php';
$translator = Translator::getInstance();
$currentLang = $translator->getCurrentLanguage();
$languages = $translator->getAvailableLanguages();

// Handle language change
if (isset($_GET['change_lang'])) {
    $newLang = $_GET['change_lang'];
    if ($translator->setLanguage($newLang)) {
        // Redirect to remove the language parameter from URL
        $currentUrl = $_SERVER['REQUEST_URI'];
        $cleanUrl = preg_replace('/[?&]change_lang=[^&]*/', '', $currentUrl);
        $cleanUrl = preg_replace('/[?&]lang=[^&]*/', '', $cleanUrl);
        
        // Add language parameter
        $separator = strpos($cleanUrl, '?') !== false ? '&' : '?';
        $redirectUrl = $cleanUrl . $separator . 'lang=' . $newLang;
        
        header('Location: ' . $redirectUrl);
        exit;
    }
}
?>

<!-- Language Switcher Widget -->
<div class="language-switcher-container">
    <div class="language-switcher-dropdown">
        <button class="language-switcher-btn" id="languageSwitcherBtn" onclick="toggleLanguageDropdown()">
            <span class="current-lang-flag"><?php echo $languages[$currentLang]['flag']; ?></span>
            <span class="current-lang-name"><?php echo $languages[$currentLang]['name']; ?></span>
            <i class="fas fa-chevron-down language-dropdown-icon"></i>
        </button>
        
        <div class="language-dropdown-menu" id="languageDropdownMenu">
            <div class="language-dropdown-header">
                <i class="fas fa-globe"></i>
                <span><?php echo __('select_language'); ?></span>
            </div>
            
            <?php foreach ($languages as $code => $lang): ?>
                <a href="?change_lang=<?php echo $code; ?>" 
                   class="language-option <?php echo $code === $currentLang ? 'active' : ''; ?>"
                   onclick="changeLanguage('<?php echo $code; ?>')">
                    <span class="lang-flag"><?php echo $lang['flag']; ?></span>
                    <span class="lang-name"><?php echo $lang['name']; ?></span>
                    <?php if ($code === $currentLang): ?>
                        <i class="fas fa-check lang-check"></i>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
