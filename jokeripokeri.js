var suitNames = 'HSDC'.split('');
var cardValues = '23456789TJQKA'.split('');

function C(n, k) {
  if (k > n) return 0;
  if (k * 2 > n) k = n-k;
  if (k == 0) return 1;

  var result = n;
  for(var i = 2; i <= k; ++i ) {
    result *= (n-i+1);
    result /= i;
  }
  return result;
}

// Generate combinations
function next_combi(c, n, max) {
  var i;
  for(i=n-1; i>=0; i--) {
    c[i]++;
    if(c[i] <= max-n+i+1) break;
  }
  if(i<0) return false;
  for(i++; i<n; i++) c[i] = c[i-1]+1;
  return true;
}

function numcomp(a,b) { return a-b; }

function win(a) {
  var h = a.slice(0);
  h.sort(numcomp);

  var c = [1,0,0,0,0], n = 0;
  var flush = (h[0] & 3) == (h[1] & 3) && (h[0] & 3) == (h[2] & 3) &&
    (h[0] & 3) == (h[3] & 3);

  if(h[4]==52) { // joker
		h[0] >>= 2;
		for (var i = 1; i < 4; i++) {
			h[i] >>= 2;
			if (h[i] != h[i - 1]) n++;
			c[n]++;
		}

		if(c[0] == 4) return 50; // five of a kind
		if(c[0] == 3 || c[1] == 3) return 15; // four of a kind
		if(c[2] == 0) return 8; // full house (has to be 2+2)
        if(c[0] == 2 || c[1] == 2 || c[2] == 2) return 2;

		if(h[3] - h[0] <= 4 || (h[2] <= 3 && h[3] == 12))
			return flush ? 30 : 3; // straight (flush)
    } else {
		flush = flush && (h[0] & 3) == (h[4] & 3);

		h[0] >>= 2;
		for (var i = 1; i < 5; i++) {
			h[i] >>= 2;
			if (h[i] != h[i - 1]) n++;
			c[n]++;
		}

        if(c[2]==0) return (c[0]==4 || c[1]==4) ? 15 : 8;
        if(c[3]==0) return 2;

		if (n == 4 && (h[4] - h[0] == 4 || (h[3] == 3 && h[4] == 12)))
			return flush ? 30 : 3; // Straight (flush)

	}

	return flush ? 4 : 0;
}

function ONE_BIT(v) {
  return !(v & (v-1))
}

function is_paired(h) {
  for(var i = 0; i < 4; i++)
    if((h[i] >> 2) == (h[i + 1] >> 2))
      return true;
  return false;
}

// Find optimal selection for a sorted hand
function optimal_selection(h) {
  var left = [];
  var ans = [];
  var c, hp;

  for(c=0, lp=0, hp=0; c<53; c++)
    if(hp < 5 && h[hp] == c) hp++; else left.push(c);

  var paired = is_paired(h);

  var S, I, n, sel = [0,0,0,0,0], ci;

  for(var s=1; s<32; s++) {
    // no single selection optimums with a pair or better in hand
    if(ONE_BIT(s) && paired) continue; 
    // With joker, you have to select the joker, not toss it
    if(h[4]==52 && s<16) continue;

    S = I = n = 0; ci = [0,1,2,3];

    for(var j=0; j<5; j++) if((s>>j)&1) sel[n++] = h[j];

    do {
      for(var j=0; j<5-n; j++) sel[n+j] = left[ci[j]];
      S+=win(sel);
      I++;
    } while(next_combi(ci, 5-n, 53-5-1));

    ans.push({p: S/I, s: s});
  }

  return ans;
}

/**
 * Shuffles array in place.
 * @param {Array} a items An array containing the items.
 */
function shuffle(a) {
    var j, x, i;
    for (i = a.length - 1; i > 0; i--) {
        j = Math.floor(Math.random() * (i + 1));
        x = a[i];
        a[i] = a[j];
        a[j] = x;
    }
    return a;
}
