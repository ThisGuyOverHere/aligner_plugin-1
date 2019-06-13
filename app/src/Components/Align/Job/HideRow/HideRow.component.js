import React, {Component} from 'react';
import PropTypes from "prop-types";
import ProjectActions from "../../../../Actions/Project.actions";

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
    };

    constructor(props) {
        super(props);
    }


    componentDidUpdate(prevProps, prevState, snapshot) {

    }

    componentDidMount() {

    }

    componentWillUnmount() {

    }

    render() {
        return <div className="container ui" id="hide-row-container">
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
