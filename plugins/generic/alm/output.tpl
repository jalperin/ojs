{**
 * plugins/generic/alm/output.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * ALM plugin settings
 *
 *}


<h3>Article level metrics</h3>

<div id="alm"></div>
<script>
	data_all = {$resultJson};
{literal}
	var cummulative = false;

	var margin = {top: 20, right: 20, bottom: 30, left: 40},
	    width = 300 - margin.left - margin.right,
	    height = 100 - margin.top - margin.bottom;

	data_all[0].sources.forEach(function(d) {
		if ( d.source && d.source.histories  ) {
			var raw_data = d.source.histories;
			var data,
			 	prev = 0;

			data = d3.map();
			// manipulate the cumulative counts AFTER the y domain has been set
			raw_data.forEach(function(d) {
				floored_date = d3.time.day.floor(d3.time.format.iso.parse(d.update_date))
				if ( !data.has(floored_date) ) { data.set(floored_date, 0); }

				// if values are cumulative, subtract previous
				if ( cummulative ) {
			 		diff = d.total - prev;
			     	prev = d.total;
					data.set(floored_date, data.get(floored_date) + diff);
				} else {
					data.set(floored_date, data.get(floored_date) + d.total);
				}

			});

			var x = d3.time.scale();

			var y = d3.scale.linear()
			    .range([height, 0]);

			// var xAxis = d3.svg.axis()
			//     .scale(x)
			//     .orient("bottom");

			var yAxis = d3.svg.axis()
			    .scale(y)
			    .orient("left");

			var svg = d3.select("#alm").append("svg")
			    .attr("width", width + margin.left + margin.right)
			    .attr("height", height + margin.top + margin.bottom)
			  .append("g")
			    .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

			svg.append("text")
				.text(d.source.name);

			// prev holds the value of the latest/last count
			svg.append("text")
			    .attr("transform", "translate(15," + (margin.top + 10) + ")")
				.text(prev);

			svg.append("rect")
			    .attr("width", width)
			    .attr("height", height)
			    .attr("transform", "translate(50,0)")
				.attr("fill", "grey")
				.attr("fill-opacity", 0.1);

			// x.domain(data.map(function(d) { return d3.time.format("%Y-%m-%dT%H:%M:%SZ").parse(d.updated_at); }));
			var published_date = d3.time.format.iso.parse(data_all.article.publication_date);

			// want to set to 30 days regardless of what is actually in array
//			x.domain([published_date, d3.time.day.offset(published_date, 365*5)]);
			x.domain([d3.time.day.offset(published_date, 365*4), d3.time.day.offset(published_date, 365*5)]);


			x.range([0, width]);
			x.ticks(d3.time.days, 1);
			x.tickFormat("%b %d");

			y.domain([0, d3.max(data.values())]);

			svg.append("g")
			    .attr("class", "x axis")
			    .attr("transform", "translate(50," + height + ")");
			    // .call(xAxis);


			// svg.append("g")
			//     .attr("class", "y axis")
			//     .call(yAxis)
			//   .append("text")
			//     .attr("transform", "rotate(-90)")
			//     .attr("y", 6)
			//     .attr("dy", ".71em")
			//     .style("text-anchor", "end")
			//     .text("Frequency");


			svg.selectAll(".bar")
			    .data(data.keys())
			  .enter().append("rect")
			    .attr("class", "bar")
			    .attr("x", function(d) { return 50+x(d3.time.format.iso.parse(d)); })
			    .attr("width", width/30)
			    .attr("y", function(d) { return y(data.get(d)); })
			    .attr("height", function(d) { return height - y(data.get(d)); })
				.attr("fill", "steelblue");
			}
	});
{/literal}
</script>

