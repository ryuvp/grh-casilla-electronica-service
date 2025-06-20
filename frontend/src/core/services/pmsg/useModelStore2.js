// store/useModelStore.js
import { defineStore } from 'pinia';
import { reactive, computed } from 'vue';
import { resolveRelations, exportRelations, globalExportRelations, areObjEquals } from './helpers';

export default function createModelStore(model, customConfig = {}) {
  const config = { ...model.getStoreConfig(), ...customConfig };
  
  let check =(d,data)=>{return d[config.key] === data[config.key]}
  if ( config.hasKey){
    if(Array.isArray(config.key)){
      let check_str = config.key.reduce((a, key)=>a+` d['${key}'] === data['${key}'] &&`,'(d,data)=>{return')
      check_str = check_str.substring(0, check_str.length-2)
      check_str+='}'
      check = eval(check_str)
    }
  }else{
    console.warn(`El modulo ${config.moduleAlias} no tiene Keys, esto reducira el rendimiento` )
    check=(d,data)=>{
      return areObjEquals(d,data)
    }
  }

  // Definición del store
  return defineStore(config.moduleAlias, () => {
    // Estado reactivos
    const state = reactive({
      itemSelected        : { loading: false, ...model.getDefault() },
      items               : [],
      keysAsinc           : [],
      keysTemp            : [],
      key                 : config.key,
      moduleAlias         : config.moduleAlias,
      maxRelationsResolve : config.maxRelationsResolve,
      relations           : config.relations,
      syncStatus          : config.sync,
      selectedStatus      : false,
      timeOutAsinc        : null,
      check,
    });

    // Getters
    const getters = {
      key     : computed(() => state.key),
      default : computed(() => model.getDefault()),
      name    : computed(() => (id) => {
        const item = state.items.find((d) => d[state.key] === id);
        return item ? `${item[model.getNameAttribute()]}` : null;
      }),
      find : computed(() => (id, level = 1) => {
        const item = state.items.find((d) => d[state.key] === id);
        return item
          ? resolveRelations(item, { maxRelationsResolve: state.maxRelationsResolve, key: state.key, relations: state.relations }, {}, level)
          : model.getDefault();
      }),
      list   : computed(() => state.items.map((item) => resolveRelations(item, { maxRelationsResolve: state.maxRelationsResolve, key: state.key, relations: state.relations }, {}))),
      filter : computed(() => (filterFn, level = 1) =>
        state.items.filter(filterFn).map((item) => resolveRelations(item, { maxRelationsResolve: state.maxRelationsResolve, key: state.key, relations: state.relations }, {}, level))
      ),
      selected : computed(() => state.selectedStatus ? resolveRelations(state.itemSelected, { maxRelationsResolve: state.maxRelationsResolve, key: state.key, relations: state.relations }, {}) : state.selectedStatus),
    };

    // Acciones
    const actions = {
      checkAsinc(key) {
        return new Promise((resolve) => {
          const ftime = () => {
            const keys = [...state.keysTemp];
            state.keysAsinc.push(...keys);
            state.keysTemp = [];
            const params = { [state.key]: ['IN'].concat(keys) };
            this.getSome(params);
          };

          if (Array.isArray(key)) {
            const keys = key.filter((d) => !state.items.some((d1) => d1[state.key] === d) && !state.keysAsinc.includes(d) && !state.keysTemp.includes(d));
            if (keys.length > 0) state.keysTemp.push(...keys);
            if (!state.timeOutAsinc) state.timeOutAsinc = setTimeout(ftime, 100);
            resolve(keys);
          } else {
            const item = state.items.find((d) => d[state.key] === key);
            if (!item && !state.keysAsinc.includes(key) && !state.keysTemp.includes(key)) state.keysTemp.push(key);
            if (!state.timeOutAsinc) state.timeOutAsinc = setTimeout(ftime, 100);
            resolve(item);
          }
        });
      },

      async show(id) {
        const response = await model.show(id);
        this.syncItem(response.data);
        return response;
      },

      async get(params = {}) {
        const action = this.syncStatus ? 'sync' : 'setItems';
        if (!model.saved()) {
          return new Promise((resolve, reject) => {
            model.getAll(params).then((response) => {
              const items = response.data.data;
              model.save(items);
              this[action](items);
              this.afterGet();
              resolve(response);
            }).catch(reject);
          });
        } else {
          this.setItems(model.getFromLocalStorage());
          this.afterGet();
        }
      },

      async getSome(params = {}) {
        const response = await model.getAll(params);
        this.sync(response.data);
        this.afterGet();
        return response;
      },

      async clearAndGet(params = {}) {
        state.items = [];
        const response = await model.getAll(params);
        this.setItems(response.data);
        this.afterGet();
        return response;
      },

      afterGet() {
        // Lógica después de obtener datos
      },

      async create(data) {
        const response = await model.create(data);
        this.syncItem(response.data);
        return response;
      },

      async update(data) {
        const response = await model.update(data);
        this.syncItem(response.data);
        return response;
      },

      async delete(data) {
        await model.delete(data);
        const index = state.items.findIndex((d) => state.check(d, data));
        if (index !== -1) state.items.splice(index, 1);
      },

      setItems(items) {
        if (state.relations.length > 0 && items.length > 0) {
          const relations = state.relations.filter((relation) => items[0][relation.alias || relation.attribute] !== undefined);
          items = globalExportRelations(items, relations, {});
        }
        state.items = items;
      },

      setSyncStatus(syncStatus) {
        state.syncStatus = syncStatus;
      },

      sync(data) {
        if (Array.isArray(data)) {
          this.syncItems(data);
        } else {
          this.syncItem(data);
        }
      },

      addToRelation({ relation, id, relations }) {
        const index = state.items.findIndex((d) => d[state.key] === id);
        if (index !== -1) {
          state.items[index][relation] = Array.isArray(relations)
            ? [...state.items[index][relation], ...relations]
            : [...state.items[index][relation], relations];
        }
      },

      syncItems(items) {
        items = items.filter((data, index, array) => array.findIndex((d) => state.check(d, data)) === index);
        if (state.relations.length > 0 && items.length > 0) {
          const relations = state.relations.filter((relation) => items[0][relation.alias || relation.attribute] !== undefined);
          items = globalExportRelations(items, relations, {});
        }
        const insert = items.filter((item) => {
          const i = state.items.findIndex((d) => state.check(d, item));
          if (i > -1) {
            state.items[i] = { ...state.items[i], ...item };
            return false;
          }
          return true;
        });
        state.items = [...state.items, ...insert];
      },

      syncItem(item) {
        const existing = state.items.find((d) => state.check(d, item));
        if (existing) {
          Object.assign(existing, item);
        } else {
          state.items.push(item);
        }
      },

      selectItem(val) {
        const parameters = typeof val === 'object' ? { id: val.id, forceRequest: false, ...val } : { id: val, forceRequest: false };
        return new Promise(async (resolve, reject) => {
          if (state.itemSelected[state.key] !== parseInt(parameters.id)) {
            state.itemSelected = { ...model.getDefault(), loading: true };
            const d = state.items.filter((d) => parseInt(d[state.key]) === parseInt(parameters.id));
            if (d.length === 1 && !parameters.forceRequest) {
              state.itemSelected = { ...d[0], loading: false };
              this.afterSelect();
              resolve({ status: true, data: d[0] });
            } else {
              try {
                const response = await this.show(parameters.id);
                const d = state.items.filter((d) => parseInt(d[state.key]) === parseInt(parameters.id));
                state.itemSelected = { ...d[0], loading: false };
                this.afterSelect();
                resolve(response);
              } catch (error) {
                reject(error);
              }
            }
          } else {
            resolve({ status: true, data: state.itemSelected });
          }
        });
      },

      afterSelect() {
        // Lógica después de seleccionar un objeto
      },

      deselect() {
        state.selectedStatus = false;
        state.itemSelected = model.getDefault();
      },
    };

    return { state, getters, ...actions };
  });
}