#! /bin/bash
for i in $(seq -f "%02g" 01 44)
do
   wget https://developer.accuweather.com/sites/default/files/$i-s.png
done
