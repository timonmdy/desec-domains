<div class="box box-primary">
  <div class="box-header with-border">
    <h3 class="box-title"><code>{{ $domain['name'] }}</code></h3>
    <a href="{{ $root }}?view=domains" class="btn btn-default btn-sm pull-right">Back to domains</a>
  </div>
  <div class="box-body">
    @if ($status['ok'])
      <p>
        Minimum TTL: <strong>{{ $status['info']['minimum_ttl'] ?? 'n/a' }}</strong> seconds ·
        Published: <strong>{{ ($status['info']['published'] ?? null) ?: 'n/a' }}</strong>
      </p>
      @if (!empty($status['info']['keys']))
        <p class="text-muted small" style="margin-bottom: 4px">DS records for your registrar:</p>
        @foreach ($status['info']['keys'] as $key)
          @foreach ($key['ds'] ?? [] as $ds)
            <pre style="margin-bottom: 4px">{{ $ds }}</pre>
          @endforeach
        @endforeach
      @endif
    @else
      <div class="alert alert-danger" style="margin-bottom: 0">{{ $status['error'] }}</div>
    @endif
  </div>
  <div class="box-footer">
    <form action="{{ $root }}" method="POST" style="display: inline">
      {{ csrf_field() }}
      <input type="hidden" name="action" value="domain.refresh" />
      <input type="hidden" name="id" value="{{ $domain['id'] }}" />
      <button type="submit" class="btn btn-default">Refresh from deSEC</button>
    </form>
    <form action="{{ $root }}" method="POST" class="pull-right" onsubmit="return confirm('Disconnect {{ $domain['name'] }}? Nothing is deleted at deSEC.')">
      {{ csrf_field() }}
      <input type="hidden" name="action" value="domain.disconnect" />
      <input type="hidden" name="id" value="{{ $domain['id'] }}" />
      <button type="submit" class="btn btn-danger" @disabled(count($allocations) > 0) title="{{ count($allocations) > 0 ? 'Release every allocation first' : '' }}">Disconnect</button>
    </form>
  </div>
</div>

<div class="row">
  <div class="col-md-5">
    <div class="box box-primary">
      <div class="box-header with-border"><h3 class="box-title">Domain token</h3></div>
      <form action="{{ $root }}" method="POST">
        <div class="box-body">
          <p>Currently using the <strong>{{ $domain['has_token'] ? 'domain-specific token' : 'default token' }}</strong>.</p>
          <div class="form-group">
            <label for="dsc-domain-token" class="control-label">New token</label>
            <input id="dsc-domain-token" type="password" name="token" autocomplete="new-password" class="form-control" />
            <p class="text-muted small">Leave empty and save to fall back to the default token.</p>
          </div>
        </div>
        <div class="box-footer">
          {{ csrf_field() }}
          <input type="hidden" name="action" value="domain.token" />
          <input type="hidden" name="id" value="{{ $domain['id'] }}" />
          <button type="submit" class="btn btn-primary pull-right">Save token</button>
        </div>
      </form>
    </div>
  </div>

  <div class="col-md-7">
    <div class="box box-primary">
      <div class="box-header with-border"><h3 class="box-title">Server allocations</h3></div>
      <div class="box-body table-responsive no-padding">
        <table class="table table-hover">
          <thead><tr><th>Host</th><th>Target</th><th>Port</th><th>Status</th><th></th></tr></thead>
          <tbody>
            @forelse ($allocations as $allocation)
              <tr>
                <td><code>{{ $allocation['host'] }}</code></td>
                <td>{{ $allocation['target'] }}</td>
                <td>{{ $allocation['port'] }}</td>
                <td><span class="label label-{{ $allocation['status'] === 'active' ? 'success' : 'danger' }}" title="{{ $allocation['last_error'] }}">{{ $allocation['status'] }}</span></td>
                <td class="text-right"><a class="btn btn-xs btn-default" href="{{ $root }}?view=server&amp;server={{ $allocation['server_id'] }}">Server</a></td>
              </tr>
            @empty
              <tr><td colspan="5" class="text-center text-muted">Not allocated to any server.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="box box-primary">
  <div class="box-header with-border"><h3 class="box-title">Records</h3></div>
  <div class="box-body table-responsive no-padding">
    @if ($recordError)
      <div class="alert alert-danger" style="margin: 15px">{{ $recordError }}</div>
    @endif
    <table class="table table-hover">
      <thead><tr><th>Name</th><th>Type</th><th>TTL</th><th>Values</th><th></th></tr></thead>
      <tbody>
        @forelse ($records as $record)
          <tr>
            <td><code>{{ $record['subname'] === '' ? '@' : $record['subname'] }}</code></td>
            <td>{{ $record['type'] }}</td>
            <td>{{ $record['ttl'] }}</td>
            <td style="word-break: break-all">{!! implode('<br>', array_map('e', $record['records'])) !!}</td>
            <td class="text-right" style="white-space: nowrap">
              @if ($record['managed'])
                <span class="label label-info" title="Created by a server allocation. Release the allocation to remove it.">Managed</span>
              @else
                <a class="btn btn-xs btn-default" href="{{ $root }}?view=domain&amp;id={{ $domain['id'] }}&amp;edit_subname={{ rawurlencode($record['subname']) }}&amp;edit_type={{ $record['type'] }}#dsc-editor">Edit</a>
                <form action="{{ $root }}" method="POST" style="display: inline" onsubmit="return confirm('Delete this record set?')">
                  {{ csrf_field() }}
                  <input type="hidden" name="action" value="record.delete" />
                  <input type="hidden" name="domain_id" value="{{ $domain['id'] }}" />
                  <input type="hidden" name="subname" value="{{ $record['subname'] }}" />
                  <input type="hidden" name="type" value="{{ $record['type'] }}" />
                  <button type="submit" class="btn btn-xs btn-danger">Delete</button>
                </form>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="5" class="text-center text-muted">No records.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="box box-primary" id="dsc-editor">
  <div class="box-header with-border"><h3 class="box-title">{{ $edit ? 'Edit record set' : 'Add or replace a record set' }}</h3></div>
  <form action="{{ $root }}" method="POST">
    <div class="box-body">
      <div class="row">
        <div class="form-group col-md-3">
          <label for="dsc-subname" class="control-label">Name</label>
          <input id="dsc-subname" type="text" name="subname" value="{{ old('subname', $edit['subname'] ?? '') }}" class="form-control" placeholder="@ for the apex" />
        </div>
        <div class="form-group col-md-2">
          <label for="dsc-type" class="control-label">Type</label>
          <select id="dsc-type" name="type" class="form-control">
            @foreach ($recordTypes as $type)
              <option value="{{ $type }}" @selected(old('type', $edit['type'] ?? 'A') === $type)>{{ $type }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group col-md-2">
          <label for="dsc-ttl" class="control-label">TTL</label>
          <input id="dsc-ttl" type="number" name="ttl" value="{{ old('ttl', $edit['ttl'] ?? 3600) }}" min="60" class="form-control" />
        </div>
        <div class="form-group col-md-5">
          <label for="dsc-records" class="control-label">Values, one per line</label>
          <textarea id="dsc-records" name="records" rows="3" class="form-control" placeholder="203.0.113.10">{{ old('records', isset($edit) ? implode("\n", $edit['records']) : '') }}</textarea>
          <p class="text-muted small">Enter values in presentation format. TXT values need their quotes, and CNAME, MX and SRV targets need a trailing dot.</p>
        </div>
      </div>
    </div>
    <div class="box-footer">
      {{ csrf_field() }}
      <input type="hidden" name="action" value="record.save" />
      <input type="hidden" name="domain_id" value="{{ $domain['id'] }}" />
      <button type="submit" class="btn btn-primary pull-right">Save record set</button>
    </div>
  </form>
</div>
