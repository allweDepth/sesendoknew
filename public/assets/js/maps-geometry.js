(function (root) {
  "use strict";
  const clone = (value) => JSON.parse(JSON.stringify(value));
  const clipping = () => {
    const library = root.polygonClipping || (typeof require === "function" ? require("../vendor/polygon-clipping/polygon-clipping.min.js") : null);
    if (!library) throw new Error("Pustaka operasi poligon tidak tersedia.");
    return library;
  };
  const polygonGeometry = (coordinates) => coordinates.length === 1 ? { type: "Polygon", coordinates: coordinates[0] } : { type: "MultiPolygon", coordinates };
  const polygon = (feature) => {
    if (!feature.geometry.type.includes("Polygon")) throw new Error("Operasi ini hanya berlaku untuk poligon.");
    return feature.geometry.coordinates;
  };
  const featureWith = (feature, geometry) => ({ type: "Feature", properties: clone(feature.properties), geometry });
  function splitPolygon(feature, start, end) {
    const dx = end[0] - start[0], dy = end[1] - start[1];
    if (Math.hypot(dx, dy) < 1e-12) throw new Error("Garis pemotong membutuhkan dua titik berbeda.");
    // Clip the world rectangle to each half-plane; intersect with the selected polygon.
    const signed = (p) => dx * (p[1] - start[1]) - dy * (p[0] - start[0]);
    const half = (sign) => {
      const world = [[-180, -90], [180, -90], [180, 90], [-180, 90]];
      const output = [];
      for (let i = 0; i < world.length; i++) {
        const a = world[i], b = world[(i + 1) % world.length];
        const sa = sign * signed(a), sb = sign * signed(b);
        if (sa >= 0) output.push(a);
        if ((sa < 0 && sb > 0) || (sa > 0 && sb < 0)) {
          const t = sa / (sa - sb);
          output.push([a[0] + (b[0] - a[0]) * t, a[1] + (b[1] - a[1]) * t]);
        }
      }
      if (output.length < 3) return [];
      return [clipping().intersection(polygon(feature), [[...output, output[0]]])];
    };
    const sides = [half(1).flat(1), half(-1).flat(1)];
    if (sides.some((side) => !side.length)) throw new Error("Garis pemotong harus melewati bagian dalam poligon.");
    return sides.flatMap((side) => side.map((coordinates) => featureWith(feature, { type: "Polygon", coordinates })));
  }
  function booleanFeatures(first, second, operation) {
    if (!["union", "intersection", "difference", "xor"].includes(operation)) throw new Error("Operasi boolean tidak valid.");
    const output = clipping()[operation](polygon(first), polygon(second));
    if (!output.length) throw new Error("Hasil operasi kosong; feature asal tetap dipertahankan.");
    return featureWith(first, polygonGeometry(output));
  }
  function splitLine(feature, path, node) {
    const output = clone(feature);
    const type = output.geometry.type;
    if (!["LineString", "MultiLineString"].includes(type)) throw new Error("Pilih feature garis.");
    const line = type === "LineString" ? output.geometry.coordinates : output.geometry.coordinates[path[0]];
    if (!line || !Number.isInteger(node) || node <= 0 || node >= line.length - 1) throw new Error("Pilih node di tengah garis, bukan node ujung.");
    const lines = [line.slice(0, node + 1), line.slice(node)];
    const result = lines.map((coordinates) => featureWith(feature, { type: "LineString", coordinates }));
    if (type === "MultiLineString") {
      output.geometry.coordinates.forEach((part, index) => {
        if (index !== path[0]) result.push(featureWith(feature, { type: "LineString", coordinates: part }));
      });
    }
    return result;
  }
  function continueLine(feature, path, atStart, added) {
    const output = clone(feature);
    if (!["LineString", "MultiLineString"].includes(output.geometry.type)) throw new Error("Pilih feature garis.");
    const line = output.geometry.type === "LineString" ? output.geometry.coordinates : output.geometry.coordinates[path[0]];
    if (!line || !added.length) throw new Error("Klik minimal satu titik untuk melanjutkan garis.");
    if (atStart) line.unshift(...clone(added).reverse()); else line.push(...clone(added));
    return output;
  }
  function explodeFeature(feature) {
    const geometry = feature.geometry;
    if (geometry.type === "MultiPolygon") return geometry.coordinates.map((coordinates) => featureWith(feature, { type: "Polygon", coordinates: clone(coordinates) }));
    if (geometry.type === "MultiLineString") return geometry.coordinates.map((coordinates) => featureWith(feature, { type: "LineString", coordinates: clone(coordinates) }));
    if (geometry.type === "MultiPoint") return geometry.coordinates.map((point) => featureWith(feature, { type: "MultiPoint", coordinates: [clone(point)] }));
    throw new Error("Feature ini sudah berupa satu bagian.");
  }
  function divideLayer(features, field) {
    if (!field) throw new Error("Pilih field pemisah layer.");
    const groups = new Map();
    features.forEach((feature) => {
      const value = feature.properties[field] ?? null;
      const key = JSON.stringify(value);
      if (!groups.has(key)) groups.set(key, { value, features: [] });
      groups.get(key).features.push(clone(feature));
    });
    if (groups.size < 2) throw new Error("Field membutuhkan minimal dua nilai berbeda untuk membagi layer.");
    if (groups.size > 50) throw new Error("Maksimal 50 layer hasil pemisahan. Pilih field dengan kategori lebih sedikit.");
    return [...groups.values()];
  }
  const api = { splitPolygon, splitLine, continueLine, booleanFeatures, explodeFeature, divideLayer };
  root.MapsGeometry = api;
  if (typeof module !== "undefined") module.exports = api;
})(typeof window !== "undefined" ? window : globalThis);
