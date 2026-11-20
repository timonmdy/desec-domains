<form action="{{ $root }}" method="POST">
  @foreach ($sections as $title => $fields)
    <div class="box box-primary">
      <div class="box-header with-border"><h3 class="box-title">{{ $title }}</h3></div>
      <div class="box-body">
        <div class="row">
          @foreach ($fields as $key => $field)
            <div class="form-group col-md-6">
              <label for="dsc-{{ $key }}" class="control-label">{{ $field['label'] }}</label>
              @if ($field['type'] === 'select')
                <select id="dsc-{{ $key }}" name="{{ $key }}" class="form-control">
                  @foreach ($field['options'] as $value => $label)
                    <option value="{{ $value }}" @selected(old($key, $values[$key]) === $value)>{{ $label }}</option>
                  @endforeach
                </select>
              @elseif ($field['type'] === 'bool')
                <select id="dsc-{{ $key }}" name="{{ $key }}" class="form-control">
                  <option value="1" @selected($values[$key])>Enabled</option>
                  <option value="0" @selected(!$values[$key])>Disabled</option>
                </select>
              @elseif ($field['type'] === 'int')
                <input id="dsc-{{ $key }}" type="number" name="{{ $key }}" value="{{ old($key, $values[$key]) }}" min="{{ $field['min'] }}" max="{{ $field['max'] }}" class="form-control" />
              @elseif ($field['type'] === 'secret')
                <input id="dsc-{{ $key }}" type="password" name="{{ $key }}" value="" autocomplete="new-password" class="form-control" placeholder="{{ $secretSet[$key] ? 'A token is stored. Type to replace it.' : 'Paste the token' }}" />
                @if ($secretSet[$key])
                  <label class="small" style="font-weight: normal; margin-top: 5px"><input type="checkbox" name="{{ $key }}_clear" value="1" /> Remove the stored token</label>
                @endif
              @else
                <input id="dsc-{{ $key }}" type="text" name="{{ $key }}" value="{{ old($key, $values[$key]) }}" class="form-control" />
              @endif
              @isset($field['help'])
                <p class="text-muted small">{{ $field['help'] }}</p>
              @endisset
              @error($key)
                <p class="text-danger small">{{ $message }}</p>
              @enderror
            </div>
          @endforeach
        </div>
      </div>
    </div>
  @endforeach

  <div class="box box-primary">
    <div class="box-footer">
      {{ csrf_field() }}
      <input type="hidden" name="action" value="settings.save" />
      <button type="submit" class="btn btn-primary pull-right">Save settings</button>
      <button type="submit" name="action" value="settings.test" class="btn btn-default" formnovalidate>Test the stored token</button>
    </div>
  </div>
</form>
