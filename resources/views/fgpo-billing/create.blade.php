@extends('layouts.app')

@section('title', 'Job PO | Bills')

@section('content')
  <div class="row">
    <form action="{{ route('fgpo-bills.store') }}" method="POST" enctype="multipart/form-data">
      @csrf
      @if ($errors->has('error'))
        <strong class="text-danger">{{ $errors->first('error') }}</strong>
      @endif
      <div class="row">
        <div class="col-12 mb-4">
          <section class="card">
            <header class="card-header">
              <div style="display: flex;justify-content: space-between;">
                <h2 class="card-title">New Bill</h2>
              </div>
            </header>
            <div class="card-body">
              <div class="row mb-4">
                <div class="col-12 col-md-2">
                  <label>Bill No</label>
                  <input type="text" class="form-control" placeholder="Bill #" disabled/>
                </div>
                <div class="col-12 col-md-2 mb-3">
                  <label>Vendor</label>
                  <select name="vendor_id" data-plugin-selecttwo class="form-control select2-js" required>
                    <option value="" selected disabled>Select Vendor</option>
                    @foreach ($coa as $item)
                      <option value="{{ $item->id }}">{{ $item->name }}</option> 
                    @endforeach
                  </select>
                </div>
                <div class="col-12 col-md-2">
                  <label>Bill Date</label>
                  <input type="date" name="bill_date" class="form-control" value="{{ date('Y-m-d') }}" required/>
                </div>
                <div class="col-12 col-md-2 mb-3">
                  <label>Ref Bill #</label>
                  <input type="number" name="ref_bill" class="form-control" placeholder="Ref Bill #"/>
                </div>
              </div>
            </div>
          </section>
        </div>

        <div class="col-12 mb-4">
          <section class="card">
            <header class="card-header">
              <h2 class="card-title">Bill Details</h2>
            </header>
            <div class="card-body">
              <div class="row">
                <div class="col-12 col-md-4 mb-3">
                  <label>Search PO No.</label>
                  <select multiple data-plugin-selecttwo class="form-control select2-js" id="poSelect" required>
                    <option value="" disabled>Select PO</option>
                    @foreach ($fgpo as $item)
                      <option value="{{$item->id}}">{{$item->doc_code}}-{{$item->id}} </option> 
                    @endforeach
                  </select>
                </div>
                <div class="col-12 col-md-2">
                  <label>PO Details</label>
                  <button type="button" class="d-block btn btn-success" onclick="getPODetails()">Get Details</button>
                </div>
                <div class="col-12 col-md-1">
                  <label>Refresh</label>
                  <button type="button" class="d-block btn btn-danger"><i class="bx bx-refresh"></i></button>
                </div>
              </div>
              <table class="table table-bordered" id="myTable">
                <thead>
                  <tr>
                    <th>PO#</th>
                    <th>Items</th>
                    <th>Quantity Ordered</th>
                    <th>Quantity Received</th>
                    <th>Rate</th>
                    <th>Total</th>
                  </tr>
                </thead>
                <tbody id="POBillTbleBody"></tbody>
              </table>
            </div>
          </section>
        </div>

        <div class="col-12 col-md-12">
          <section class="card">
            <header class="card-header" style="display: flex;justify-content: space-between;">
              <h2 class="card-title">Summary</h2>
            </header>
            <div class="card-body">
              <div class="row mb-3">
                <div class="col-12 col-md-2">
                  <label>Total Quantity Ordered</label>
                  <input type="number" class="form-control" id="total_fab" placeholder="Total Quantity Ordered" disabled/>
                </div>
                <div class="col-12 col-md-2">
                  <label>Total Quantity Received</label>
                  <input type="number" class="form-control" id="total_fab_amt" placeholder="Total Quantity Received" disabled />
                </div>
                <div class="col-12 col-md-2">
                  <label>Total Adjusted Amount</label>
                  <input type="number" class="form-control" id="total_adj_amount" placeholder="Total Adjusted Amount" disabled/>
                </div>
                <div class="col-12 col-md-3">
                  <label>Total Bill</label>
                  <input type="number" class="form-control" id="total_bill" placeholder="Total Bill" disabled/>
                </div>
              </div>
            </div>
            <footer class="card-footer text-end">
              <a class="btn btn-danger" href="{{ route('pur-fgpos.index') }}">Discard</a>
              <button type="submit" class="btn btn-primary">Add Bill</button>
            </footer>
          </section>
        </div>
      </div>
    </form>
  </div>

  <script>
    function getPODetails() {
      var selectedPOs = $("#poSelect").val().map(Number);
      if (selectedPOs.length === 0) {
        alert("Please select at least one PO.");
        return;
      }

      $.ajax({
        type: "GET",
        url: "{{ route('pur-fgpos.get-details') }}",
        data: { po_ids: selectedPOs },
        success: function(response) {
          if (response.success) {
            console.log(response.summary);
            populateTable(response.summary);
          } else {
            alert(response.message);
          }
        },
        error: function(xhr, status, error) {
          console.error("AJAX Error:", xhr.responseText);
          alert("Error retrieving PO details.");
        }
      });
    }

    function populateTable(summary) {
      let tbody = $("#POBillTbleBody");
      tbody.empty();

      summary.forEach((po, poIndex) => {
        let fabricDetails = po.fabrics.map(f => {
          let rate = parseFloat(f.fabric_rate) || 0;
          return `${f.fabric_name} (${rate.toFixed(2)} ${f.fabric_unit})`;
        }).join(", ");

        let totalFabricQty = po.fabrics.reduce((sum, f) => sum + (parseFloat(f.fabric_qty) || 0), 0);
        let totalFabricAmount = po.fabrics.reduce((sum, f) => {
          let qty = parseFloat(f.fabric_qty) || 0;
          let rate = parseFloat(f.fabric_rate) || 0;
          return sum + (qty * rate);
        }, 0).toFixed(2);

        let totalReceivedQty = po.products.reduce((sum, p) => sum + (parseFloat(p.received_qty) || 0), 0);
        let overallConsumption = totalReceivedQty > 0 ? (totalFabricQty / totalReceivedQty).toFixed(2) : "0.00";

        // Fabric summary row
        let mainRow = `
          <tr class="table-secondary">
            <td rowspan="${po.products.length + 1}">${po.fgpo_id}
              <input type="hidden" name="details[${poIndex}][production_id]" value="${po.fgpo_id}">
            </td> 
            <td colspan="2"><strong>Fabric:</strong> ${fabricDetails}</td>
            <td><strong>Consumption:</strong> ${overallConsumption}</td>
            <td><strong>Fabric Amount:</strong> ${totalFabricAmount}</td>
            <td>
              <input type="number" name="details[${poIndex}][adjusted_amount]" class="form-control adjustment-input" placeholder="Adjusted Amount" value="0">
            </td>
          </tr>
        `;
        tbody.append(mainRow);

        // Product rows
        po.products.forEach((product, prodIndex) => {
          let receivedQty = parseFloat(product.received_qty) || 0;
          let orderedQty = parseFloat(product.ordered_qty) || 0;

          let productRow = `
            <tr>
              <td>
                ${product.product_name}
                <input type="hidden" name="details[${poIndex}][products][${prodIndex}][product_id]" value="${product.product_id}">
              </td>
              <td>${orderedQty}</td>
              <td>
                ${receivedQty}
                <input type="hidden" name="details[${poIndex}][products][${prodIndex}][received_qty]" value="${receivedQty}">
              </td>
              <td>
                <input type="number" class="form-control rate-input"
                  name="details[${poIndex}][products][${prodIndex}][rate]"
                  data-received-qty="${receivedQty}" value="0">
              </td>
              <td class="total-amount">0.00</td>
            </tr>
          `;
          tbody.append(productRow);
        });
      });

      // Auto-calc row totals
      $(".rate-input").on("input", function () {
        let rate = parseFloat($(this).val()) || 0;
        let receivedQty = parseFloat($(this).data("received-qty")) || 0;
        let total = (rate * receivedQty).toFixed(2);
        $(this).closest("tr").find(".total-amount").text(total);
      });
    }
  </script>
@endsection
