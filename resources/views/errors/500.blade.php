@extends('errors.layout')

@section('code', 'ERROR 500')
@section('title', 'Something went wrong at our end')
@section('message', 'This is a fault in the site, not anything you did. It has been written to the log; please try again shortly.')

{{-- Timestamped so a report of "it broke" can be matched to the right line in
     storage/logs without guesswork. Deliberately no exception detail: with
     APP_DEBUG off this page is what strangers see. --}}
@section('reference', now()->utc()->format('Y-m-d H:i:s').' UTC')
