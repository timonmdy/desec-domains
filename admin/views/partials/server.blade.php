@include('admin.servers.partials.navigation', ['server' => $serverModel])

<div class="box box-primary">
  <div class="box-header with-border">
    <h3 class="box-title">Domains for {{ $server['name'] }}</h3>
  </div>
  <div class="box-body">
    @if (!$server['port'])
      <div class="alert alert-warning" style="margin-bottom: 0">This server has no main allocation, so there is no port to point to.</div>
    @else
      <p style="margin-bottom: 0">
        The SRV records point to the main allocation port <strong>{{ $server['port'] }}</strong>.
        Address records use the public IP <strong>{{ $server['publicIp'] ?? 'none found' }}</strong>.
      </p>
    @endif
  </div>
</div>

<div class="box box-primary">
  <div class="box-header with-border"><h3 class="box-title">Allocated domains</h3></div>
  <div class="box-body table-responsive no-padding">
    <table class="table table-hover">
      <thead><tr><th>Host</th><th>Type</th><th>Target</th><th>Port</th><th>Status</th><th></th></tr></thead>
      <tbody>
        @forelse ($allocations as $allocation)
          <tr>
            <td><code>{{ $allocation['host'] }}</code></td>
            <td>{{ $allocation['kind'] === 'domain' ? 'Whole domain' : 'Subdomain' }}</td>
            <td>{{ $allocation['target'] }}</td>
            <td>{{ $allocation['port'] }}</td>
            <td>
              <span class="label label-{{ $allocation['status'] === 'active' ? 'success' : 'danger' }}" title="{{ $allocation['last_error'] }}">{{ $allocation['status'] }}</span>
              @foreach ($allocation['notes'] as $note)
                <p class="small text-warning" style="margin: 4px 0 0">{{ $note }}</p>
              @endforeach
            </td>
            <td class="text-right">
              <form action="{{ $root }}" method="POST" onsubmit="return confirm('Release {{ $allocation['host'] }} and remove its DNS records?')">
                {{ csrf_field() }}
                <input type="hidden" name="action" value="allocation.release" />
                <input type="hidden" name="id" value="{{ $allocation['id'] }}" />
                <button type="submit" class="btn btn-xs btn-danger">Release</button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="6" class="text-center text-muted">No domains allocated to this server.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="box box-primary">
  <div class="box-header with-border"><h3 class="box-title">Allocate a domain</h3></div>
  @if (count($domains) === 0)
    <div class="box-body"><p class="text-muted" style="margin: 0">Connect a domain first under <a href="{{ $root }}?view=domains">Domains</a>.</p></div>
  @else
    <form action="{{ $root }}" method="POST" id="dsc-allocate">
      <div class="box-body">
        <div class="row">
          <div class="form-group col-md-4">
            <label for="dsc-domain" class="control-label">Domain</label>
            <select id="dsc-domain" name="domain_id" class="form-control">
              @foreach ($domains as $domain)
                <option value="{{ $domain['id'] }}" @selected((int) old('domain_id') === $domain['id'])>{{ $domain['name'] }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group col-md-4">
            <label class="control-label">What to allocate</label>
            <div>
              <label style="font-weight: normal"><input type="radio" name="kind" value="subdomain" @checked(old('kind', 'subdomain') === 'subdomain') /> Subdomain</label>
              &nbsp;
              <label style="font-weight: normal"><input type="radio" name="kind" value="domain" @checked(old('kind') === 'domain') /> Whole domain</label>
            </div>
          </div>
          <div class="form-group col-md-4" data-for="subdomain">
            <label for="dsc-subdomain" class="control-label">Subdomain</label>
            <input id="dsc-subdomain" type="text" name="subdomain" value="{{ old('subdomain') }}" class="form-control" placeholder="survival" />
            <p class="text-muted small">Creates {{ $service }}.&lt;subdomain&gt;. Several labels such as eu.survival are allowed.</p>
          </div>
          <div class="form-group col-md-4" data-for="domain">
            <label class="control-label">Wildcard</label>
            <div><label style="font-weight: normal"><input type="checkbox" name="wildcard" value="1" @checked(old('wildcard', '1')) /> Also answer for every subdomain</label></div>
            <p class="text-warning small">A wildcard SRV record answers lookups for every service name under the domain that has no record of its own, not only {{ $service }}. Do not use it on a domain that also serves mail, Matrix, XMPP or similar.</p>
          </div>
        </div>
        <div class="row">
          <div class="form-group col-md-6">
            <label for="dsc-target" class="control-label">Target override (optional)</label>
            <input id="dsc-target" type="text" name="target" value="{{ old('target') }}" class="form-control" placeholder="Uses the target from the extension settings" />
            <p class="text-muted small">Advanced: a host name that resolves to the server. Address records are only managed for targets inside the connected domain.</p>
          </div>
        </div>
      </div>
      <div class="box-footer">
        {{ csrf_field() }}
        <input type="hidden" name="action" value="allocation.create" />
        <input type="hidden" name="server_id" value="{{ $server['id'] }}" />
        <button type="submit" class="btn btn-primary pull-right" @disabled(!$server['port'])>Allocate</button>
      </div>
    </form>
    <script>
      (() => {
        const form = document.getElementById('dsc-allocate');
        const update = () => {
          const kind = form.querySelector('input[name="kind"]:checked').value;
          form.querySelectorAll('[data-for]').forEach((el) => { el.style.display = el.dataset.for === kind ? '' : 'none'; });
        };
        form.querySelectorAll('input[name="kind"]').forEach((el) => el.addEventListener('change', update));
        update();
      })();
    </script>
  @endif
</div>
