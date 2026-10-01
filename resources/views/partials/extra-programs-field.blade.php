{{--
  "Also handles" checkboxes for Program Coordinators.
  Params:
    $fieldId (string) unique id prefix
    $programSelect (string) id of the home program <select>
    $selected (array) program codes currently ticked
    $homeProgram (string|null) current home program
--}}
@php
    $selected = array_values((array) ($selected ?? []));
    $homeProgram = $homeProgram ?? null;
@endphp
<fieldset class="form-group extra-programs" data-extra-programs data-program-select="{{ $programSelect }}">
    <legend class="form-label">Also handles <span class="extra-programs__optional">(optional)</span></legend>
    <div class="extra-programs__list">
        @foreach(\App\Models\Program::labels() as $code => $programLabel)
            <label class="extra-programs__item" data-extra-program="{{ $code }}" @if($homeProgram === $code) hidden @endif>
                <input type="checkbox" name="extra_programs[]" value="{{ $code }}"
                       @checked(in_array($code, $selected, true)) @disabled($homeProgram === $code)>
                <span><strong>{{ $code }}</strong> <span class="extra-programs__name">{{ \Illuminate\Support\Str::after($programLabel, ' — ') }}</span></span>
            </label>
        @endforeach
    </div>
    <small class="text-xs text-gray-500 dark:text-gray-400">This coordinator also manages documents, subjects, reviews, analytics and Teacher's Load for the ticked programs.</small>
</fieldset>

@once
    @push('scripts')
    <script>
    (function () {
        function sync(field) {
            var select = document.getElementById(field.dataset.programSelect);
            var home = select ? select.value : '';
            field.querySelectorAll('[data-extra-program]').forEach(function (item) {
                var isHome = item.dataset.extraProgram === home;
                var box = item.querySelector('input');
                item.hidden = isHome;
                box.disabled = isHome;
                if (isHome) box.checked = false;
            });
        }
        document.querySelectorAll('[data-extra-programs]').forEach(function (field) {
            var select = document.getElementById(field.dataset.programSelect);
            if (select) select.addEventListener('change', function () { sync(field); });
            sync(field);
        });
    })();
    </script>
    @endpush
@endonce
