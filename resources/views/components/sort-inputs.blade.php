@props(['sorting' => ['sort' => null, 'direction' => null]])

@if(request()->filled('sort') && ($sorting['sort'] ?? null) !== null)
    <input type="hidden" name="sort" value="{{ $sorting['sort'] }}">
    <input type="hidden" name="direction" value="{{ $sorting['direction'] }}">
@endif
