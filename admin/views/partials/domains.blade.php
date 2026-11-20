<div class="content-header" data-desec-page-header>
  <h1>Domains<small>Registered deSEC domains on the system</small></h1>
</div>

<div class="row">
  <div class="col-md-5">
    <div class="box box-primary">
      <div class="box-header with-border"><h3 class="box-title">Connect a domain</h3></div>
      <form action="{{ $root }}" method="POST">
        <div class="box-body">
          @unless ($hasDefaultToken)
            <div class="alert alert-warning">No default token is set. Add one in the extension settings, or give this domain its own token below.</div>
          @endunless
          <div class="form-group">
            <label for="dsc-name" class="control-label">Domain</label>
            <input id="dsc-name" type="text" name="name" value="{{ old('name') }}" list="dsc-discoverable" class="form-control" placeholder="example.com" required />
            <datalist id="dsc-discoverable">
              @foreach ($discoverable as $name)
                <option value="{{ $name }}"></option>
              @endforeach
            </datalist>
            <p class="text-muted small">The domain must already exist in your deSEC account. Suggestions are the zones the default token can see.</p>
          </div>
          <div class="form-group">
            <label for="dsc-token" class="control-label">Domain token (optional)</label>
            <input id="dsc-token" type="password" name="token" autocomplete="new-password" class="form-control" />
            <p class="text-muted small">Leave empty to use the default token. A token scoped to this domain only keeps the blast radius small.</p>
          </div>
        </div>
        <div class="box-footer">
          {{ csrf_field() }}
          <input type="hidden" name="action" value="domain.connect" />
          <button type="submit" class="btn btn-primary pull-right">Connect domain</button>
        </div>
      </form>
    </div>
  </div>

  <div class="col-md-7">
    <div class="box box-primary">
      <div class="box-header with-border"><h3 class="box-title">Connected domains</h3></div>
      <div class="box-body table-responsive no-padding">
        <table class="table table-hover">
          <thead>
            <tr><th>Domain</th><th>deSEC</th><th>Token</th><th>Allocations</th><th></th></tr>
          </thead>
          <tbody>
            @forelse ($domains as $domain)
              <tr>
                <td><code>{{ $domain['name'] }}</code></td>
                <td>
                  @if ($domain['status']['ok'])
                    <span class="label label-success">Reachable</span>
                  @else
                    <span class="label label-danger" title="{{ $domain['status']['error'] }}">Error</span>
                  @endif
                </td>
                <td>{{ $domain['has_token'] ? 'Own token' : 'Default token' }}</td>
                <td>{{ $domain['allocationCount'] }}</td>
                <td class="text-right"><a class="btn btn-xs btn-primary" href="{{ $root }}?view=domain&amp;id={{ $domain['id'] }}">Manage</a></td>
              </tr>
            @empty
              <tr><td colspan="5" class="text-center text-muted">No domains connected yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
