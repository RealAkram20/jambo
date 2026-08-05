function e(e){let t=globalThis.customElements;!t||t.get(e.tagName)||t.define(e.tagName,e)}
/**
* @license
* Copyright 2021 Google LLC
* SPDX-License-Identifier: BSD-3-Clause
*/
var t=class extends Event{constructor(e,t,n,r){super(`context-request`,{bubbles:!0,composed:!0}),this.context=e,this.contextTarget=t,this.callback=n,this.subscribe=r??!1}};
/**
* @license
* Copyright 2021 Google LLC
* SPDX-License-Identifier: BSD-3-Clause
*/
function n(e){return e}const r=n(Symbol.for(`@videojs/player`)),i=n(Symbol.for(`@videojs/media`)),a=n(Symbol.for(`@videojs/container`)),o=Object.freeze({length:0,start:()=>0,end:()=>0}),s=Object.assign(new EventTarget,{length:0,*[Symbol.iterator](){},getTrackById:()=>null}),c=new EventTarget;Object.freeze({});export{i as a,t as c,a as i,e as l,s as n,r as o,o as r,n as s,c as t};
//# sourceMappingURL=constants-Bnj-5J6p.js.map