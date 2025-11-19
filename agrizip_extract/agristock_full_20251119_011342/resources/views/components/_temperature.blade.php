<script>
    var app = {};
    var tempdiv = document.getElementById('graphtemperature');
    var temp = echarts.init(tempdiv);
    let option;
    
    const posList = [
      'left',
      'right',
      'top',
      'bottom',
      'inside',
      'insideTop',
      'insideLeft',
      'insideRight',
      'insideBottom',
      'insideTopLeft',
      'insideTopRight',
      'insideBottomLeft',
      'insideBottomRight'
    ];
    app.configParameters = {
      rotate: {
        min: -90,
        max: 90
      },
      align: {
        options: {
          left: 'left',
          center: 'center',
          right: 'right'
        }
      },
      verticalAlign: {
        options: {
          top: 'top',
          middle: 'middle',
          bottom: 'bottom'
        }
      },
      position: {
        options: posList.reduce(function (map, pos) {
          map[pos] = pos;
          return map;
        }, {})
      },
      distance: {
        min: 0,
        max: 100
      }
    };
    app.config = {
      rotate: 90,
      align: 'left',
      verticalAlign: 'middle',
      position: 'insideBottom',
      distance: 15,
      onChange: function () {
        const labelOption = {
          rotate: app.config.rotate,
          align: app.config.align,
          verticalAlign: app.config.verticalAlign,
          position: app.config.position,
          distance: app.config.distance
        };
        myChart.setOption({
          series: [
            { label: labelOption },
            { label: labelOption },
            { label: labelOption },
            { label: labelOption }
          ]
        });
      }
    };
    const labelOption = {
      show: true,
      position: app.config.position,
      distance: app.config.distance,
      align: app.config.align,
      verticalAlign: app.config.verticalAlign,
      rotate: app.config.rotate,
      formatter: '{c}  {name|{a}}',
      fontSize: 16,
      rich: { name: {} }
    };
    option = {
      tooltip: {
        trigger: 'axis',
        axisPointer: {
          type: 'shadow'
        }
      },
      legend: {
        data: ['Steppe', 'Desert']
      },
      toolbox: {
        show: true,
        orient: 'vertical',
        left: 'right',
        top: 'center',
        feature: {
          mark: { show: true },
          dataView: { show: true, readOnly: false },
          magicType: { show: true, type: ['line', 'bar', 'stack'] },
          restore: { show: true },
          saveAsImage: { show: true }
        }
      },
        xAxis: [
            {
                type: 'category',
                axisTick: { show: false },
                data: [
                    @foreach($temperatures as $item)
                    {{ $item->session }}, 
                    @endforeach
                ]
            }
        ],
        yAxis: [
            { type: 'value' }
        ],
        series: [
            
            {
                name: 'STOCKAGE A SEC',
                type: 'bar',
                label: labelOption,
                emphasis: { focus: 'series' },
                data: [
                    @foreach($temperatures->where('type_storage', 'STOCKAGE A SEC') as $item)
                        {{ $item->degree }},
                    @endforeach
                ]
            },
            
            {
                name: 'STOCKAGE REFRIGERE',
                type: 'bar',
                label: labelOption,
                emphasis: { focus: 'series' },
                data: [
                    @foreach($temperatures->where('type_storage', 'STOCKAGE REFRIGERE') as $item)
                        {{ $item->degree }},
                    @endforeach
                ]
            }
        ]
    };
    
    option && temp.setOption(option);
</script>