<script>
    var storage = document.getElementById('graphstorage');
    var chartstorage = echarts.init(storage);

    const getChartOptions = () => {
        const isMobile = window.innerWidth < 768;

        return {
            title: {
                text: 'Zones de Stockage',
                left: 'center'
            },
            tooltip: {
                trigger: 'item'
            },
            legend: {
                orient: isMobile ? 'horizontal' : 'vertical',
                top: isMobile ? 'bottom' : 'middle',
                left: isMobile ? 'center' : 'left'
            },
            series: [
                {
                    name: 'Storage Areas',
                    type: 'pie',
                    radius: isMobile ? ['55%', '80%'] : ['40%', '70%'],
                    center: ['50%', isMobile ? '45%' : '50%'],
                    avoidLabelOverlap: false,
                    itemStyle: {
                        borderRadius: 10,
                        borderColor: '#fff',
                        borderWidth: 2
                    },
                    label: {
                        show: false,
                        position: 'center'
                    },
                    emphasis: {
                        label: {
                            show: true,
                            fontSize: 24,
                            fontWeight: 'bold'
                        }
                    },
                    labelLine: {
                        show: false
                    },
                    data: [
                        @foreach($storages as $item)
                            { value: {{ $item->stocks->count() }}, name: '{{ $item->name }}' },
                        @endforeach
                    ]
                }
            ]
        };
    };

    chartstorage.setOption(getChartOptions());

    window.addEventListener('resize', () => {
        chartstorage.resize();
        chartstorage.setOption(getChartOptions());
    });
</script>
