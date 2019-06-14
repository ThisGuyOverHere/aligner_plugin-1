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
        selectedInNavigator: PropTypes.bool
    };

    constructor(props) {
        super(props);
        this.state = {
            selected: false
        }
    }


   /* componentDidUpdate(prevProps, prevState, snapshot) {
        if(prevProps.navigatorIndex !== this.props.navigatorIndex){
            console.log("changed##############")
            this.isSelected()
        }
    }*/

    /*componentDidMount() {
        ProjectStore.addListener(ProjectConstants.HIDE_SEGMENTS_NAVIGATOR, this.isSelected);
    }

    componentWillUnmount() {
        ProjectStore.removeListener(ProjectConstants.HIDE_SEGMENTS_NAVIGATOR, this.isSelected);
    }*/

    render() {
        return <div className={`container ui ${ this.props.selectedInNavigator ? "selected" : null}`} id="hide-row-container">
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

    isSelected = () => {
        console.log("ROW index: ", this.props.index );
        console.log("navigator######: ", this.props.navigatorIndex );
        this.setState({
            selected: this.props.navigatorIndex === this.props.index
        })
    }

}

export default HideComponent;
