<header class="mp-topbar">
    <button class="mp-menu" type="button" onclick="toggleMPanelSidebar()" aria-label="Open menu">☰</button>
    <div class="mp-search"><span>⌕</span><input type="search" placeholder="Search mPanel..." aria-label="Search mPanel"></div>
    <div class="mp-top-actions">
        <button class="mp-icon-btn" type="button" onclick="toggleMPanelTheme()" title="Toggle theme">◐</button>
        <button class="mp-icon-btn" type="button" title="Notifications">♢</button>
        <div class="mp-user">
            <div class="mp-avatar">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</div>
            <strong>{{ auth()->user()->name }}</strong>
        </div>
        <form method="POST" action="{{ route('logout') }}" style="margin:0">
            @csrf
            <button class="mp-icon-btn" type="submit" title="Sign out">↪</button>
        </form>
    </div>
</header>
