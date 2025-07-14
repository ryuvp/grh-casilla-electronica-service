
import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import Widget from '@ckeditor/ckeditor5-widget/src/widget';
import UpcastWriter from '@ckeditor/ckeditor5-engine/src/view/upcastwriter';
import { toWidget, viewToModelPositionOutsideModelElement } from '@ckeditor/ckeditor5-widget/src/utils';


//
// The variable editor plugin.
//

class dropVariable extends Plugin {
  static get requires() {
    return [ Widget ];
  }

  init() {
    this._defineSchema();
    this._defineConverters();
    this._defineClipboardInputOutput();

    // View-to-model position mapping is needed because an variable element in the model is represented by a single element,
    // but in the view it is a more complex structure.
    this.editor.editing.mapper.on(
      'viewToModelPosition',
      viewToModelPositionOutsideModelElement( this.editor.model, viewElement =>  viewElement.hasClass( 'variable' ) )
    );
  }

  _defineSchema() {
    this.editor.model.schema.register( 'variable', {
      allowWhere      : '$text',
      isInline        : true,
      isObject        : true,
      //isContent       : true,
      allowAttributes : [ 'class', 'variable', 'special', 'alias', 'italic', 'bold', 
        'underline', 'heading', 'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor' ]
    } );
  }

  _defineConverters() {
    const conversion = this.editor.conversion;

    // Data-to-model conversion.
    conversion.for( 'upcast' ).elementToElement( {
      view : {
        name    : 'variable',
        classes : [ 'variable' ]
      },
      model : ( viewElement, { writer } ) => {
        return writer.createElement( 'variable', getDataFromViewElement( viewElement ) );
      }
    } );

    // Model-to-data conversion.
    conversion.for( 'dataDowncast' ).elementToElement( {
      model : 'variable',
      view  : ( modelItem, { writer: viewWriter } ) => createCardData( modelItem, viewWriter )
    } );

    // Model-to-view conversion.
    conversion.for( 'editingDowncast' ).elementToElement( {
      model : 'variable',
      view  : ( modelItem, { writer: viewWriter } ) => toWidget( createCardView( modelItem, viewWriter ), viewWriter )
    } );

    // Helper method for both downcast converters.
    function createCardView( modelItem, viewWriter,) {
      const variable = modelItem.getAttribute( 'variable' );
      let special = modelItem.getAttribute( 'special' );
      special = special==undefined?'':special
      const alias = modelItem.getAttribute( 'alias' );
      let clase = modelItem.getAttribute( 'class' );
      clase = clase?`list-group-item-${clase} ${clase}`:'variable list-group-item-danger danger'
      const cardView = viewWriter.createContainerElement( 'span', 
        { class           : clase, 
          'data-variable' : variable,
          'data-special'  : special,
          'data-class'    : modelItem.getAttribute( 'class' ), 
          'alias'         : alias, 
          style           : 'color:black;' } 
      );
      viewWriter.insert( viewWriter.createPositionAt( cardView, 0 ),  viewWriter.createText( `${alias}` ) );
      return cardView;
    }
    function createCardData( modelItem, viewWriter,) {
      const variable = modelItem.getAttribute( 'variable' );
      let special = modelItem.getAttribute( 'special' );
      special = special==undefined?'':special
      const alias = modelItem.getAttribute( 'alias' );
      const cardView = viewWriter.createContainerElement( 'variable', 
        { 
          class           : 'variable', 
          ':variable'     : variable,
          ':special'      : "'"+special+"'",
          'data-variable' : variable,
          'data-special'  : special,
          'data-class'    : modelItem.getAttribute( 'class' ),
          'alias'         : alias 
        } );
      viewWriter.insert( viewWriter.createPositionAt( cardView, 0 ),viewWriter.createText( `` ) );
      return cardView;
    }
  }

  // Integration with the clipboard pipeline.
  _defineClipboardInputOutput() {
    const view = this.editor.editing.view;
    const viewDocument = view.document;

    // Processing pasted or dropped content.
    this.listenTo( viewDocument, 'clipboardInput', ( evt, data ) => {
      // The clipboard content was already processed by the listener on the higher priority
      // (for example while pasting into the code block).
      if ( data.content ) {
        return;
      }
      const variableData = data.dataTransfer.getData( 'variable' );

      if ( !variableData ) {
        return;
      }
      // Use JSON data encoded in the DataTransfer.
      const variable = JSON.parse( variableData );

      // Translate the variable data to a view fragment.
      const writer = new UpcastWriter( viewDocument );
      const fragment = writer.createDocumentFragment();

      //aqui se agrega la etiqueta
      writer.appendChild(
        writer.createElement( 'variable', 
          { 
            class           : 'variable', 
            ':variable'     : variable.variable,
            ':special'      : "'"+variable.special+"'",
            'data-variable' : variable.variable,
            'data-special'  : variable.special,
            'data-class'    : variable.class, 
            'alias'         : variable.alias }, [

          ] ),
        fragment
      );

      // Provide the content to the clipboard pipeline for further processing.
      data.content = fragment;
    } );

    // Processing copied, pasted or dragged content.
    this.listenTo( document, 'clipboardOutput', ( evt, data ) => {
      if ( data.content.childCount != 1 ) {
        return;
      }
      const viewElement = data.content.getChild( 0 );
      if ( viewElement.is( 'element', 'span' ) && viewElement.hasClass( 'variable' ) ) {
        data.dataTransfer.setData( 'variable', JSON.stringify( getDataFromInnerElement( viewElement ) ) );
      }
    } );
  }
}

//
// variable helper functions.
//

function getDataFromViewElement( viewElement ) {
  return {
    class    : viewElement.getAttribute('data-class'),
    variable : viewElement.getAttribute('data-variable'),
    special  : viewElement.getAttribute('data-special'),
    alias    : viewElement.getAttribute('alias')
  };
}
function getDataFromInnerElement( viewElement ) {
  return {
    class    : viewElement.getAttribute('data-class'),
    variable : viewElement.getAttribute('data-variable'),
    special  : viewElement.getAttribute('data-special'),
    alias    : getText( viewElement )
  };
}

function getText( viewElement ) {
  return Array.from( viewElement.getChildren() )
    .map( node => node.is( '$text' ) ? node.data : '' )
    .join( '' );
}
export default dropVariable