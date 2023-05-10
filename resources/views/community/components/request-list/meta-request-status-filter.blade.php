<label class="text-xs font-bold md:-mb-6">Request status</label>
<div class="space-x-6 flex" id="filter-by-request-status">
    <label class="cursor-pointer flex items-center gap-x-1 text-xs">
        <input type="radio" id="all-requests" name="request-status" value="0" {{ !$selectedRequestStatus ? 'checked' : '' }} @change="handleRequestStatusChanged">
        All
    </label>

    <label class="cursor-pointer flex items-center gap-x-1 text-xs">
        <input type="radio" id="all-requests" name="request-status" value="1" {{ $selectedRequestStatus == 1 ? 'checked' : '' }} @change="handleRequestStatusChanged">
        Claimed
    </label>

    <label class="cursor-pointer flex items-center gap-x-1 text-xs">
        <input type="radio" id="all-requests" name="request-status" value="2" {{ $selectedRequestStatus == 2 ? 'checked' : '' }} @change="handleRequestStatusChanged">
        Unclaimed
    </label>
</div>