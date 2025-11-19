<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css"/>

<div class="row">
    <div class="col-xxl-9">
        <div class="row">
            <div class="col-xl-4 col-md-4 col-sm-4 col-xs-12">
                <div class="card bg-danger-subtle" style="background: url({{ asset('images/dashboard/dashboard-shape-1.png') }}); background-repeat: no-repeat; background-position: bottom center; ">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="avatar avatar-sm avatar-label-danger">
                                <i class="mdi mdi-buffer mt-1"></i>
                            </div>
                            <div class="ms-3">
                                <p class="text-danger mb-1">@lang('locale.billing', ['suffix'=>'s'])</p>
                                <h4 class="mb-0">{{ moneyFormat($billings->sum('amount')) }}</h4>
                            </div>
                        </div>
                        <div class="mt-3 mb-2">
                            <p class="mb-0">@lang('locale.total') : {{ $billings->count() }}</p>                                
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-4 col-sm-4 col-xs-12">
                <div class="card bg-success-subtle" style="background: url({{ asset('images/dashboard/dashboard-shape-2.png') }}); background-repeat: no-repeat; background-position: bottom center; ">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="avatar avatar-sm avatar-label-success">
                                <i class="mdi mdi-cash-usd-outline mt-1"></i>
                            </div>
                            <div class="ms-3">
                                <p class="text-success mb-1">@lang('locale.payment', ['suffix'=>'s'])</p>
                                <h4 class="mb-0">{{ moneyFormat($payments->sum('amount')) }}</h4>
                            </div>
                        </div>
                        <div class="mt-3 mb-2">
                            <p class="mb-0">@lang('locale.total') : {{ $payments->count() }}</p>                                
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-4 col-sm-4 col-xs-12">
                <div class="card bg-info-subtle" style="background: url({{ asset('images/dashboard/dashboard-shape-3.png') }}); background-repeat: no-repeat; background-position: bottom center; ">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="avatar avatar-sm avatar-label-info">
                                <i class="mdi mdi-webhook mt-1"></i>
                            </div>
                            <div class="ms-3">
                                <p class="text-info mb-1">@lang('locale.stock', ['suffix'=>'s'])</p>
                                <h4 class="mb-0">{{ $stocks->count() }}</h4>
                            </div>
                        </div>
                        <div class="hstack gap-2 mt-3">
                            @if (isGroupAuthorized([5, 6, 7, 8]))
                            <button class="btn btn-primary">@lang('locale.delivery', ['suffix'=>app()->getLocale() == 'en' ? 'y' : ''])</button>
                            <button class="btn btn-danger">@lang('locale.filter')</button>
                            @else
                            <p class="mb-2">{{ $releases->count() }} @lang('locale.stock', ['suffix'=>'s']) @lang('locale.released')</p>
                            @endif                                
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- end row -->

        <div class="row">
            <div class="col-md-8 col-sm-8 col-xs-12">
                <div class="card border">
                    <div class="card-header bg-success-subtle">
                        <h3 class="card-title">
                            <i class="fas fa-cart-plus fs-14 text-muted"></i> 10 @lang('locale.last_stocks')
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover table-bordered table-striped dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead>
                                    <th>#</th>
                                    <th>@lang('locale.customer', ['suffix'=>''])</th>
                                    <th>@lang('locale.ref')</th>
                                    <th>@lang('locale.billing', ['suffix'=>''])</th>
                                    <th>@lang('locale.qty')</th>
                                    <th>@lang('locale.expired_at')</th>
                                </thead>
                                <tbody>
                                    @foreach ($stocks as $item)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $item->customer->name }}</td>
                                        <td>{{ $item->ref }}</td>
                                        <td>{{ $item->billing->ref }}</td>
                                        <td>{{ $item->qty }} kg</td>
                                        <td class="text-{{ $item->created_at->addDays($item->expired_at) >= now() ? 'primary' : 'danger' }}">
                                            @if ($item->qty == 0)
                                                <div class="text-primary text-italic">@lang('locale.released')</div>
                                            @else
                                                {{ date('d/m/Y', strtotime($item->created_at->addDays($item->expired_at))) ." / ".$item->expired_at }} @lang('locale.days')
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-4 col-xs-12">
                <div class="card">
                    <div class="card-header card-header-bordered bg-info-subtle">
                        <h3 class="card-title">
                            <i class="fas fa-newspaper"></i>
                            @lang('locale.flash')
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="slider responsive">
                            @foreach ($incidents as $item)
                            <div class="card mb-0 text-center">
                                <h6>{{ $item->type }}</h6>
                                <p class="text-muted" style="text-align: justify">
                                    {{ $item->description ?? 'No Description' }}
                                </p>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- end row -->

<div class="row">
    <div class="col-md-8 col-sm-8 col-xs-12">
        <div class="card" style="height: 416px; overflow: hidden auto;" data-simplebar="">
            <div class="card-header card-header-bordered">
                <div class="card-icon text-muted"><i class="fas fa-money-bill fs14"></i></div>
                <h3 class="card-title">5 @lang('locale.billing', ['suffix'=>'s'])</h3>
            </div>
            <div class="card-body">
                <div class="rich-list rich-list-flush">
                    @forelse ($billings as $item)
                    <div class="flex-column align-items-stretch">
                        <div class="rich-list-item">
                            <div class="rich-list-prepend">
                                <div class="avatar avatar-xs">
                                    <div class=""><img src="https://img.icons8.com/cotton/100/stack-of-money--v3.png" alt="Avatar image" class="avatar-2xs" /></div>
                                </div>
                            </div>
                            <div class="rich-list-content">
                                <h4 class="rich-list-title mb-1">@lang('locale.billing', ['suffix'=>'']) : {{ $item->ref }} | @lang('locale.customer', ['suffix'=>'']) : {{ $item->customer->name }}</h4>
                                <p class="rich-list-subtitle mb-0">{{ date('d/m/Y H:i:s', strtotime($item->created_at)) }} | @lang('locale.amount') : {{ moneyFormat($item->amount) }} | @lang('locale.discount') : {{ moneyFormat($item->discount) }}</p>
                            </div>
                            <div class="rich-list-append"><a class="btn btn-sm btn-label-{{ $item->payments->sum('amount') == $item->amount ? 'success' : (!$item->delayed_at->isFuture() ? 'danger' : 'warning') }}" href="{{ route('stocks.show', $item->stock_id) }}">@lang('locale.stock', ['suffix'=>''])</a></div>
                        </div>
                    </div>
                    @empty
                    <p class="text-muted text-center">@lang('locale.empty')</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-4 col-xs-12">
        <div class="card">
            <div class="card-header justify-content-between">
                <div class="card-icon text-muted"><i class="fas fa-temperature-low fs-14"></i></div>
                <h4 class="card-title">@lang('locale.temperature', ['suffix'=>'s'])</h4>
                <div class="card-addon dropdown"></div>
            </div>
            <div class="card-body">
                <div class="border-bottom hstack justify-content-center gap-4 pb-3">
                    <div class="text-center">
                        <div class="d-flex align-items-center justify-content-center">
                            @if (is_null($temperatures->first()))
                            <p class="text-muted text-danger">Aucune température recueillie</p>
                            @else
                            <span class="text-primary fs-22 me-2"><i class="fas fa-thumbs-up"></i></span>
                            <h4 class="display-6 mb-0">
                                {{ $temperatures->where('created_at', date('Y-m-d'))->sortByDesc('id')->first()->degree }}
                            </h4>
                            @endif
                        </div>
                        <p class="text-muted mb-0">@lang('locale.cold_storage')</p>
                    </div>   
                </div>
                <div class="border-bottom hstack justify-content-center gap-4 py-3">
                    <div class="text-center">
                        <div class="d-flex align-items-center justify-content-center">
                            <h4 class="display-6 mb-0" id="ambient-temp"></h4>
                        </div>
                        <p class="text-muted mb-0">@lang('locale.ambient_temperature')</p>
                    </div>
                </div>
                <div class="border-bottom hstack justify-content-center gap-4 py-3">
                    @foreach ($temperatures as $item)
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h5 class="fs-6 mb-0"><i class="fas fa-temperature-high text-{{ $item->degree > 37 ? 'danger' : 'primary' }} me-2"></i> {{ $item->session }}</h5>
                        <p class="text-muted mb-0">{{ $item->degree }}°C</p>
                    </div> 
                    @endforeach
                </div>
                <div class="pt-3">
                    <div id="graphtemperature"></div>
                </div>
            </div>
        </div>
    </div>            
</div>
<!-- end row -->

<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/echarts/5.6.0/echarts.min.js"></script>
<script>
    $(document).ready(function() {
        $('.slider').slick({
            autoplay: true,           // Enables auto-scrolling
            autoplaySpeed: 2000,      // Sets the speed of auto-scrolling (in ms)
            arrows: false,            // Disables navigation arrows
            dots: true,               // Adds dots for pagination
            infinite: true,           // Enables infinite loop
            speed: 500,               // Speed of sliding transition
        });


        var cityName = "Abidjan"; // Replace with your city name
        var apiKey = "{{ env('OPENWEATHER_KEY') }}"; // Replace with your API key

        fetch(`https://api.openweathermap.org/data/2.5/weather?q=${cityName}&appid=${apiKey}&units=metric`)
        .then(response => response.json())
        .then(data => {
        console.log(data);
            // Here you can process the data and display it as needed
            var weather = data.weather[0].description;
            var temperature = data.main.temp;
            $('#ambient-temp').text(temperature+'°C');
            // console.log(`Weather: ${weather}, Temperature: ${temperature}`);
        })
        .catch(error => {
            console.error("Error fetching weather data: ", error);
        });
        
        // GRAPH
        var ROOT_PATH = 'https://echarts.apache.org/examples';

        var chartDom = document.getElementById('graphtemperature');
        var myChart = echarts.init(chartDom);
        var option;
        
        const weatherIcons = {
          Sunny: ROOT_PATH + '/data/asset/img/weather/sunny_128.png',
          Cloudy: ROOT_PATH + '/data/asset/img/weather/cloudy_128.png',
          Showers: ROOT_PATH + '/data/asset/img/weather/showers_128.png'
        };
        const seriesLabel = {
          show: true
        };
        option = {
          title: { text: 'Weather Statistics' },
          tooltip: {
            trigger: 'axis',
            axisPointer: { type: 'shadow' }
          },
          legend: {
            data: ['City Alpha', 'City Beta', 'City Gamma']
          },
          grid: { left: 100 },
          toolbox: {
            show: true,
            feature: {
              saveAsImage: {}
            }
          },
          xAxis: {
            type: 'value',
            name: 'Days',
            axisLabel: { formatter: '{value}' }
          },
          yAxis: {
            type: 'category',
            inverse: true,
            data: ['Sunny', 'Cloudy', 'Showers'],
            axisLabel: {
              formatter: function (value) {
                return '{' + value + '| }\n{value|' + value + '}';
              },
              margin: 20,
              rich: {
                value: {
                  lineHeight: 30,
                  align: 'center'
                },
                Sunny: {
                  height: 40,
                  align: 'center',
                  backgroundColor: {
                    image: weatherIcons.Sunny
                  }
                },
                Cloudy: {
                  height: 40,
                  align: 'center',
                  backgroundColor: {
                    image: weatherIcons.Cloudy
                  }
                },
                Showers: {
                  height: 40,
                  align: 'center',
                  backgroundColor: {
                    image: weatherIcons.Showers
                  }
                }
              }
            }
          },
          series: [
            {
              name: 'City Alpha',
              type: 'bar',
              data: [165, 170, 30],
              label: seriesLabel,
              markPoint: {
                symbolSize: 1,
                symbolOffset: [0, '50%'],
                label: {
                  formatter: '{a|{a}\n}{b|{b} }{c|{c}}',
                  backgroundColor: 'rgb(242,242,242)',
                  borderColor: '#aaa',
                  borderWidth: 1,
                  borderRadius: 4,
                  padding: [4, 10],
                  lineHeight: 26,
                  // shadowBlur: 5,
                  // shadowColor: '#000',
                  // shadowOffsetX: 0,
                  // shadowOffsetY: 1,
                  position: 'right',
                  distance: 20,
                  rich: {
                    a: {
                      align: 'center',
                      color: '#fff',
                      fontSize: 18,
                      textShadowBlur: 2,
                      textShadowColor: '#000',
                      textShadowOffsetX: 0,
                      textShadowOffsetY: 1,
                      textBorderColor: '#333',
                      textBorderWidth: 2
                    },
                    b: {
                      color: '#333'
                    },
                    c: {
                      color: '#ff8811',
                      textBorderColor: '#000',
                      textBorderWidth: 1,
                      fontSize: 22
                    }
                  }
                },
                data: [
                  { type: 'max', name: 'max days: ' },
                  { type: 'min', name: 'min days: ' }
                ]
              }
            },
            {
              name: 'City Beta',
              type: 'bar',
              label: seriesLabel,
              data: [150, 105, 110]
            },
            {
              name: 'City Gamma',
              type: 'bar',
              label: seriesLabel,
              data: [220, 82, 63]
            }
          ]
        };
    });
</script>