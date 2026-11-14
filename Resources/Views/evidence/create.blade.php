@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card">
        <div class="card-header">Upload Evidence</div>
        <div class="card-body">
            <form action="{{ route('evidence.store', ['taskId' => $taskId]) }}"
                  method="POST"
                  enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label for="file" class="form-label">File</label>
                    <input type="file"
                           class="form-control @error('file') is-invalid @enderror"
                           id="file"
                           name="file"
                           required>
                    @error('file')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="notes" class="form-label">Notes (optional)</label>
                    <textarea class="form-control @error('notes') is-invalid @enderror"
                              id="notes"
                              name="notes"
                              rows="4">{{ old('notes') }}</textarea>
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">Submit</button>
                <a href="{{ route('evidence.index', ['taskId' => $taskId]) }}"
                   class="btn btn-outline-secondary">
                    Cancel
                </a>
            </form>
        </div>
    </div>
</div>
@endsection
