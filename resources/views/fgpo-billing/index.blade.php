@extends('layouts.app')

@section('title', 'Job PO | Bills')

@section('content')
  <div class="row">
    <div class="col">
      <section class="card">
        <header class="card-header" style="display: flex;justify-content: space-between;">
          <h2 class="card-title">All Bills</h2>
          <div>
            <a class="btn btn-primary text-end" href="{{ route('fgpo-bills.create') }}"  aria-expanded="false" > <i class="fa fa-plus"></i> New Bill</a>
          </div>
        </header>
        <div class="card-body">
          <div>
            <div class="col-md-5" style="display:flex;">
              <select class="form-control" style="margin-right:10px" id="columnSelect">
                <option selected disabled>Search by</option>
                <option value="1">Date</option>
                <option value="2">vendor</option>
              </select>
              <input type="text" class="form-control" id="columnSearch" placeholder="Search By Column"/>

            </div>
          </div>

          <div class="modal-wrapper table-scroll">
            <table class="table table-bordered table-striped mb-0" id="cust-datatable-default">
              <thead>
                <tr>
                  <th>S.No</th>
                  <th>Date</th>
                  <th>Vendor Name</th>
                  <th>Ref Bill #</th>
                  <th>PO Nos</th>
                  <th>Total Amount</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                @foreach($bills as $index => $bill)
                  <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($bill->bill_date)->format('d-m-Y') }}</td>
                    <td>{{ $bill->vendor->name ?? 'N/A' }}</td>
                    <td>{{ $bill->ref_bill_no ?? 'N/A' }}</td>
                    <td>{{ $bill->po_numbers }}</td>
                    <td>{{ number_format($bill->calculated_total, 2) }}</td>
                    <td>
                      <a href="{{ route('fgpo-bills.edit', $bill->id) }}" class="btn btn-sm btn-warning">
                        <i class="fa fa-edit"></i>
                      </a>
                      <form action="{{ route('fgpo-bills.destroy', $bill->id) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                          <i class="fa fa-trash"></i>
                        </button>
                      </form>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </section>
    </div>
  </div>
@endsection