@extends('layout.default')

@section('title')
    <title>Forums - Staff Dashboard - {{ config('other.title') }}</title>
@endsection

@section('meta')
    <meta name="description" content="Forums - Staff Dashboard">
@endsection

@section('breadcrumb')
    <li>
        <a href="{{ route('staff.dashboard.index') }}" itemprop="url" class="l-breadcrumb-item-link">
            <span itemprop="title" class="l-breadcrumb-item-link-title">Staff Dashboard</span>
        </a>
    </li>
    <li class="active">
        <a href="{{ route('staff.forums.index') }}" itemprop="url" class="l-breadcrumb-item-link">
            <span itemprop="title" class="l-breadcrumb-item-link-title">Forums</span>
        </a>
    </li>
@endsection

@section('content')
    <div class="container box">
        <h2>Forums</h2>
        <a href="{{ route('staff.forums.create') }}" class="btn btn-primary">Add New Category/Forum</a>
        <div class="table-responsive">
            <table class="table table-condensed table-striped table-bordered table-hover">
            <thead>
            <tr>
                <th>Name</th>
                <th>Type</th>
                <th>Position</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($categories as $c)
                <tr class="success">
                    <td>
                        <a href="{{ route('staff.forums.edit', ['slug' => $c->slug, 'id' => $c->id]) }}">{{ $c->name }}</a>
                    </td>
                    <td>Category</td>
                    <td>{{ $c->position }}</td>
                    <td>
                        <form action="{{ route('staff.forums.destroy', ['slug' => $c->slug, 'id' => $c->id]) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">@lang('common.delete')</button>
                        </form>
                    </td>
                </tr>
                @foreach ($c->getForumsInCategory()->sortBy('position') as $f)
                    <tr>
                        <td>
                            <a href="{{ route('staff.forums.edit', ['slug' => $f->slug, 'id' => $f->id]) }}">---- {{ $f->name }}</a>
                        </td>
                        <td>Forum</td>
                        <td>{{ $f->position }}</td>
                        <td>
                            <form action="{{ route('staff.forums.destroy', ['slug' => $f->slug, 'id' => $f->id]) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">@lang('common.delete')</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            @endforeach
            </tbody>
        </table>
        </div>
    </div>
@endsection
