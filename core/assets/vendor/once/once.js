/* once - v3.5.0 - 2020-11-25 */
var t = /[\11\12\14\15\40]+/, e = document;

function n(t, e, n) {return t[e + "Attribute"]("data-once", n)}

function r(e) {
  if ("string" != typeof e) {
    throw new TypeError("once ID must be a string");
  }
  if ("" === e || t.test(e)) {
    throw new RangeError("once ID must not be empty or contain spaces");
  }
  return '[data-once~="' + e + '"]'
}

function o(t) {
  if (!(t instanceof Element)) {
    throw new TypeError("The element must be an instance of Element");
  }
  return !0
}

function u(t, n) {
  if (void 0 === n && (n = e), !t) {
    throw new TypeError("Selector must not be empty");
  }
  var r = t;
  return "string" != typeof t || n !== e && !o(n) ? t instanceof Element && (r = [t]) : r = n.querySelectorAll(t), Array.prototype.slice.call(r)
}

function i(t, e, n) {
  return e.filter((function (e) {
    var r = o(e) && e.matches(t);
    return r && n && n(e), r
  }))
}

function c(e, r) {
  var o = r.add, u = r.remove, i = [];
  n(e, "has") && n(e, "get").trim().split(t).forEach((function (t) {i.indexOf(t) < 0 && t !== u && i.push(t);})), o && i.push(o);
  var c = i.join(" ");
  n(e, "" === c ? "remove" : "set", c);
}

function f(t, e, n) {return i(":not(" + r(t) + ")", u(e, n), (function (e) {return c(e, { add: t })}))}

f.remove = function (t, e, n) {return i(r(t), u(e, n), (function (e) {return c(e, { remove: t })}))},
  f.filter = function (t, e, n) {return i(r(t), u(e, n))},
  f.find = function (t, e) {return u(t ? r(t) : "[data-once]", e)};
