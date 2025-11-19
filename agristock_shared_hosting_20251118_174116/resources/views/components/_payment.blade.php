<script>
    var paymentdiv = document.getElementById('graphpayment');
    var paymentchart = echarts.init(paymentdiv);
    const weatherIcons = {
      Sunny: 'https://img.icons8.com/ios-glyphs/30/money--v1.png',
      Cloudy: 'https://img.icons8.com/external-vectorslab-flat-vectorslab/50/external-Mobile-Money-e-commerce-vectorslab-flat-vectorslab.png',
      Showers: 'https://img.icons8.com/ios-filled/50/credit-card-front.png'
    };
    paymentchart.setOption({
      tooltip: {
        trigger: 'item',
        formatter: '{a} <br/>{b} : {c} ({d}%)'
      },
      legend: {
        bottom: 10,
        left: 'center',
        data: ['FACTURE NON REGLEE', 'FACTURE REGLEE']
      },
      series: [
        {
          type: 'pie',
          radius: '65%',
          center: ['50%', '50%'],
          selectedMode: 'single',
          data: [
            {
              value: {{ $billings->where(fn($item) => $item->payments->sum('amount') == ($item->amount - $item->discount))->count() }},
              name: 'FACTURE REGLEE',
              itemStyle: {
                color: '#38c66c'
              },
              label: {
                formatter: [
                  '{title|{b}}{abg|}',
                  '  {weatherHead|METHODE DE REGLEMENT}{valueHead|%}{rateHead|Percent}',
                  '{hr|}',
                  '  {Sunny|}{value|202}{rate|55.3%}',
                  '  {Cloudy|}{value|142}{rate|38.9%}',
                  '  {Showers|}{value|21}{rate|5.8%}'
                ].join('\n'),
                backgroundColor: '#38c66c',
                borderColor: '#777',
                borderWidth: 1,
                borderRadius: 4,
                rich: {
                  title: {
                    color: '#eee',
                    align: 'center'
                  },
                  abg: {
                    backgroundColor: '#333',
                    width: '100%',
                    align: 'right',
                    height: 25,
                    borderRadius: [4, 4, 0, 0]
                  },
                  Sunny: {
                    height: 30,
                    align: 'left',
                    backgroundColor: {
                      image: weatherIcons.Sunny
                    }
                  },
                  Cloudy: {
                    height: 30,
                    align: 'left',
                    backgroundColor: {
                      image: weatherIcons.Cloudy
                    }
                  },
                  Showers: {
                    height: 30,
                    align: 'left',
                    backgroundColor: {
                      image: weatherIcons.Showers
                    }
                  },
                  weatherHead: {
                    color: '#333',
                    height: 24,
                    align: 'left'
                  },
                  hr: {
                    borderColor: '#777',
                    width: '100%',
                    borderWidth: 0.5,
                    height: 0
                  },
                  value: {
                    width: 20,
                    padding: [0, 20, 0, 30],
                    align: 'left'
                  },
                  valueHead: {
                    color: '#333',
                    width: 20,
                    padding: [0, 20, 0, 30],
                    align: 'center'
                  },
                  rate: {
                    width: 40,
                    align: 'right',
                    padding: [0, 10, 0, 0]
                  },
                  rateHead: {
                    color: '#333',
                    width: 40,
                    align: 'center',
                    padding: [0, 10, 0, 0]
                  }
                }
              }
            },
            {
              value: {{ $billings->where(fn($item) => $item->payments->sum('amount') < ($item->amount - $item->discount))->count() }},
              name: 'FACTURE NON REGLEE',
              itemStyle: {
                color: '#fe5b5b'
              }
            }
          ],
          emphasis: {
            itemStyle: {
              shadowBlur: 10,
              shadowOffsetX: 0,
              shadowColor: 'rgba(0, 0, 0, 0.5)'
            }
          }
        }
      ]
    });
</script>
