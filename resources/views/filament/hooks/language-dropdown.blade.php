<div 
    x-cloak 
    x-show="open" 
    @click.outside="open = false"
    class="absolute right-0 mt-2 w-40 bg-white dark:bg-gray-800 shadow-lg rounded-lg border border-gray-200 dark:border-gray-700"
>

    <!-- EN -->
    <a href="{{ route('language.switch', ['lang' => 'en']) }}"
        class="flex items-center gap-2 px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
    >
        <img src="https://flagcdn.com/w20/gb.png" class="w-5">
        English
    </a>

    <!-- FR -->
    <a href="{{ route('language.switch', ['lang' => 'fr']) }}"
        class="flex items-center gap-2 px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
    >
        <img src="https://flagcdn.com/w20/fr.png" class="w-5">
        Français
    </a>

    <!-- 中文 -->
    <a href="{{ route('language.switch', ['lang' => 'zh']) }}"
        class="flex items-center gap-2 px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
    >
        <img src="https://flagcdn.com/w20/cn.png" class="w-5">
        中文
    </a>

</div>
