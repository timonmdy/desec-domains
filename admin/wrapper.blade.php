@if (request()->user()?->root_admin)
<script>
  (function () {
    var base = '/admin/extensions/desec';
    var path = location.pathname.replace(/\/+$/, '');

    function syncExtensionHeader() {
      if (path !== base) return;

      var view = new URLSearchParams(location.search).get('view');
      var isManagementPage = ['domains', 'domain', 'server'].indexOf(view) !== -1;
      document.querySelectorAll('.content-header').forEach(function (header) {
        if (header.querySelector('[ext-title]')) header.hidden = isManagementPage;
      });

      document.querySelectorAll('.ext-description').forEach(function (description) {
        description.hidden = isManagementPage;
      });
    }

    function addSidebarLink() {
      var servers = document.querySelector('.sidebar-menu a[href$="/admin/servers"]');
      var item = document.querySelector('[data-desec-nav]');
      if (!item && servers) {
        item = document.createElement('li');
        item.setAttribute('data-desec-nav', '');
        item.innerHTML = '<a href="' + base + '?view=domains"><i class="fa fa-globe"></i> <span>Domains</span></a>';
        servers.parentNode.parentNode.insertBefore(item, servers.parentNode.nextSibling);
      }

      if (path !== base) return;

      var view = new URLSearchParams(location.search).get('view');
      var isDomainsPage = ['domains', 'domain'].indexOf(view) !== -1;
      var isServerDomainsPage = view === 'server';
      var isSettingsPage = !isDomainsPage && !isServerDomainsPage;
      var serversItem = servers && servers.closest('li');
      function findMenuItem(targetPath) {
        var links = document.querySelectorAll('.sidebar-menu a[href]');
        for (var index = 0; index < links.length; index += 1) {
          var linkPath = new URL(links[index].href, location.origin).pathname.replace(/\/+$/, '');
          if (linkPath === targetPath) return links[index].closest('li');
        }
        return null;
      }

      var extensionsItem = findMenuItem('/admin/extensions') || findMenuItem(base);
      var extensionPageItem = findMenuItem(base);
      var domainsItem = document.querySelector('[data-desec-nav]');
      var items = [serversItem, extensionsItem, extensionPageItem, domainsItem].filter(Boolean);

      items.forEach(function (menuItem) { menuItem.classList.remove('active'); });
      var activeItem = extensionsItem;
      if (isDomainsPage) activeItem = domainsItem;
      else if (isServerDomainsPage) activeItem = serversItem;
      else if (!isSettingsPage) activeItem = null;
      if (activeItem) activeItem.classList.add('active');
    }

    function addServerTab() {
      var view = new URLSearchParams(location.search).get('view');
      var serverId = path.match(/^\/admin\/servers\/view\/(\d+)/);
      var isDomainsPage = path === base && view === 'server';
      var id = isDomainsPage
        ? new URLSearchParams(location.search).get('server')
        : serverId && serverId[1];
      var tabs = document.querySelector('.nav-tabs-custom .nav-tabs');
      if (!id || !tabs) return;
      var item = tabs.querySelector('[data-desec-tab]');
      if (!item) {
        item = document.createElement('li');
        item.setAttribute('data-desec-tab', '');
        var domainsLink = document.createElement('a');
        domainsLink.href = base + '?view=server&server=' + encodeURIComponent(id);
        domainsLink.textContent = 'Domains';
        item.appendChild(domainsLink);
      }
      var buildLink = tabs.querySelector('a[href*="/build"]');
      var buildTab = buildLink && buildLink.closest('li');
      var insertionPoint = buildTab ? buildTab.nextSibling : tabs.querySelector('li.tab-success');
      tabs.insertBefore(item, insertionPoint);
      item.classList.toggle('active', isDomainsPage);
    }

    function run() { syncExtensionHeader(); addSidebarLink(); addServerTab(); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run); else run();
  })();
</script>
@endif
