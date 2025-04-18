const s=(o,n,a)=>{let t=new Date(o*1e3)-new Date;if(t<=0)return n(),{cancel:()=>{}};const i=setTimeout(()=>{n(),e&&clearInterval(e)},t);let e=null;return typeof a=="function"&&(e=setInterval(()=>{t-=1e3;const r=Math.floor(t/1e3);a(r),t<=0&&clearInterval(e)},1e3)),{cancel:()=>{clearTimeout(i),e&&clearInterval(e)}}};export{s as h};
//# sourceMappingURL=handleTokenExpiration-BsSYarxJ.js.map
