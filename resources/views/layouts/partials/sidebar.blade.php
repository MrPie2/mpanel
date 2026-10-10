<aside class="mp-sidebar">
    <a class="mp-brand" href="{{ route('dashboard') }}">
        <div class="mp-logo">⌁</div><span>mPanel</span>
    </a>
    <div class="mp-section-label">Control panel</div>
    <nav class="mp-nav">
        <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i>▦</i><span>Dashboard</span></a>
        <a class="{{ request()->routeIs('websites.*') || request()->routeIs('websites.index') ? 'active' : '' }}" href="{{ route('websites.index') }}"><i>◈</i><span>Websites</span></a>
        <a href="#"><i>◎</i><span>Domains</span></a>
        <a class="{{ request()->routeIs('databases.*') ? 'active' : '' }}" href="{{ route('databases.index') }}"><i>▤</i><span>Databases</span></a>
        <a href="#"><i>◇</i><span>DNS Manager</span></a>
        <a href="#"><i>▣</i><span>SSL Certificates</span></a>
        <a href="#"><i>↗</i><span>Git Deployments</span></a>
        <a class="{{ request()->routeIs('terminal.*') ? 'active' : '' }}" href="{{ route('terminal.index') }}"><i>⌘</i><span>Web Terminal</span></a>
        <a href="#"><i>◫</i><span>Backups</span></a>
    </nav>
    <div class="mp-section-label">System</div>
    <nav class="mp-nav">
        <a href="#"><i>⚙</i><span>Settings</span></a>
        <a href="#"><i>◌</i><span>Activity Log</span></a>
    </nav>
</aside>
