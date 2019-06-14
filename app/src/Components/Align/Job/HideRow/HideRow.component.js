import React, {Component} from 'react';
import PropTypes from "prop-types";
import ProjectActions from "../../../../Actions/Project.actions";
import ProjectStore from "../../../../Stores/Project.store";
import ProjectConstants from "../../../../Constants/Project.constants";

class HideComponent extends Component {

    static propTypes = {
        index: PropTypes.number.isRequired,
        animate: PropTypes.bool,
        setAnimatedRow: PropTypes.func,
        scrollY: PropTypes.any,
        enableDrag: PropTypes.bool,
        rec: PropTypes.any,
        row: PropTypes.object.isRequired,
        selection: PropTypes.object,
        isInHideNavigator: PropTypes.bool,
        selectedInNavigator: PropTypes.bool
    };

    constructor(props) {
        super(props);
        this.state = {
            selected: false
        }
    }

    render() {
        const {isInHideNavigator,selectedInNavigator} = this.props;
        return <div className={`container ui 
                    ${ selectedInNavigator ? "selected" : null} ${isInHideNavigator ? "isIn" : null}`}
                    id="hide-row-container">
            <div className="index">{this.props.index+1}</div>
            <div className="show-toggle" onClick={this.showRow}>
              <i aria-hidden='true' className='unhide icon'/>
            </div>
            <div className="row-collapsed-line"/>
        </div>
    }

    showRow = () => {
        const matches = [{
            source: this.props.row.source.order,
            target: this.props.row.target.order
        }];
        ProjectActions.showSegments(matches);
    };

}

export default HideComponent;
